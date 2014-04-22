<?php namespace Hokuken\Haik\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class InitializeCommand extends Command {

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var array site config array*/
    protected $siteConfig;

    /** @var string site main copy*/
    protected $mainCopy;

    /** @var string site sub copy*/
    protected $subCopy;

    /** @var boolean force mode*/
    protected $forceMode;

    /** @var boolean skip dialog*/
    protected $skipDialog;

    /** @var integer mode mask */
    protected $modeMask = 0777;

    protected function configure()
    {
        $this->setName('initialize')
             ->setDescription('Init haik data')
             ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force initialization to haik data.'
             )
             ->addOption(
                'skip-dialog',
                null,
                InputOption::VALUE_NONE,
                'Skip asking dialogs and set dummy data.'
             )
             ->addOption(
                'mode-mask',
                null,
                InputOption::VALUE_REQUIRED,
                'Set file mode modifying mask.',
                0777
             );
        $this->siteConfig = [];
        $this->mainCopy = $this->subCopy = '';
        $this->skipDialog = $this->forceMode = false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->forceMode = $input->getOption('force');
        $this->skipDialog = $input->getOption('skip-dialog');

        if ($input->hasOption('mode-mask'))
        {
            $this->modeMask = intval($input->getOption('mode-mask'), 0);
        }

        if ( ! $this->forceMode)
        {
            if ($this->isInitialized())
            {
                $output->writeln("<error>haik is already initialized!</error>");
                $output->writeln("<error>Abort initializing.</error>");
                return;
            }
        }
        if ($this->skipDialog)
        {
            $output->writeln("<info>Starting haik initialization without dialog...</info>\n");
            return $this->initializeAppWithoutDialog();
        }
        else
        {
    		$output->writeln("<info>Starting haik initializaion...</info>\n");
            return $this->initializeApp();
        }

    }

    protected function isInitialized()
    {
        return file_exists('./haik-contents/config/haik.ini.php');
    }

	protected function initializeApp()
	{
        $this->setSiteCopy();
        $this->setAdmin();
        if ($this->askSiteConfigIsRight())
        {
            $this->copySiteContent();
            $this->setFilePermission();
            $this->setComplete();
            $this->saveSiteCopy();
            $this->saveSiteConfig();
        }
        else
        {
            $this->output->writeln("<comment>Initialization is aborted.\n</comment>");
            return 1;
        }
	}

    protected function initializeAppWithoutDialog()
    {
        $this->siteConfig = [
            'username' => 'user@example.com',
            'passwd'   => $this->hashPassword('hogehoge')
        ];
        $this->mainCopy = 'haik v' . $this->getApplication()->getVersion();
        $this->copySiteContent();
        $this->setFilePermission();
        $this->setComplete();
        $this->saveSiteCopy();
        $this->saveSiteConfig();
    }

    protected function setSiteCopy()
    {
        //plugin_app_start_set_sitecopy
        $dialog = $this->getHelperSet()->get('dialog');

        $this->mainCopy = $dialog->ask($this->output, 'Please enter the main copy of site top page: ', '');
        $this->subCopy = $dialog->ask($this->output, 'Please enter the sub copy of site top page: ', '');
    }

    protected function setAdmin()
    {
        $dialog = $this->getHelperSet()->get('dialog');

        do {
            $email = $dialog->ask($this->output, 'Please enter the admin email address: ', 'haik');
        } while ( ! $this->validateEmail($email));
        
        do {
            $password = $dialog->askHiddenResponse($this->output, 'Please enter the admin password: ');
        } while ( ! $this->validatePassword($password));

    	$hashed_password = $this->hashPassword($password);

    	$this->siteConfig['username'] = $email;
    	$this->siteConfig['passwd']   = $hashed_password;
    }

    protected function validateEmail($email)
    {
        return strpos($email, '@') > 0;
    }

    protected function validatePassword($password)
    {
        if (strlen($password) < 8)
        {
            $this->output->writeln('<error>Password requires more than 8 characters.</error>');
            return false;
        }
        if (strlen($password) > 32)
        {
            $this->output->writeln('<error>Password should be less than 32 characters.</error>');
            return false;
        }
        if ( ! preg_match('/^[a-zA-Z0-9`~!@#$%^&*\(\)_\+=\{\}\[\]\|:;"\'<>,.?\/ -]+$/', $password))
        {
            $this->output->writeln('<error>Password must not contain invalid characters.</error>');
            return false;
        }
        return true;
    }

    protected function hashPassword($password)
    {
		require_once './haik-contents/lib/PasswordHash.php';
		$t_hasher = new \PasswordHash(8, TRUE);
		$hash = '{PHPASS}' . $t_hasher->HashPassword($password);
        return $hash;
    }

    protected function copySiteContent()
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $this->output->writeln('<comment>copying site contents...</comment>');

        $skel_dir = './haik-contents/lib/skel/';
        $dist_dir = './haik-contents/';
        if (is_dir($skel_dir))
        {
            foreach (['config', 'meta', 'skin', 'wiki'] as $dir)
            {
                $this->output->writeln('<info>'.$dir . '/ copying...</info>');

                $files = glob("{$skel_dir}{$dir}/*");
                foreach ($files as $file_path)
                {
                    $file_name = basename($file_path);
                    $dist_path = "{$dist_dir}{$dir}/" . $file_name;
                    if ( ! $this->skipDialog && ! $this->forceMode && file_exists($dist_path))
                    {
                        if ( ! $dialog->askConfirmation($this->output, "overwrite {$dist_path}? [y/N]: ", false))
                        {
                            continue;
                        }
                        system("rm -f $dist_path");
                    }
                    if ($this->forceMode)
                    {
                        system("rm -f $dist_path");
                    }
                    system("cp -p {$file_path} {$dist_path}");
                    $this->output->writeln('  put: ' . $dist_path);
                }
            }
        }

        $dist_path = './.htaccess';
        if ( ! $this->skipDialog && ! $this->forceMode && file_exists($dist_path))
        {
            if ( ! $dialog->askConfirmation($this->output, "overwrite {$dist_path}? [y/N]: ", false))
            {
                $this->output->writeln('');
                return;
            }
            system("rm -f $dist_path");
        }
        if ($this->forceMode)
        {
            system("rm -f $dist_path");
        }
        system("cp {$skel_dir}skel.htaccess ./.htaccess");

        $this->output->writeln('');
    }

    protected function setFilePermission()
    {
        // !TODO: change mode
        $command = $this->getApplication()->find('chmod');
        $arguments = array(
            'command' => 'chmod',
            '--mask'  => $this->modeMask,
        );
        $input = new ArrayInput($arguments);
        $returnCode = $command->run($input, $this->output);
    }


    protected function setComplete()
    {
        $this->siteConfig['app_start'] = 0;
    }

    protected function printSiteConfig()
    {
        $buffer  = 'Main copy : ' . $this->mainCopy . "\n";
        $buffer .= 'Sub copy  : ' . $this->subCopy . "\n";
        $buffer .= 'Email     : ' . $this->siteConfig['username'] . "\n";
        $buffer .= 'Password  : ********';

        $this->output->writeln("\n".'<info>'.$buffer.'</info>'."\n");
    }

    protected function askSiteConfigIsRight()
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $this->printSiteConfig();
        $confirm_message = '<question>Site configuration seems to OK?</question> [y/N]: ';

        if ( ! $dialog->askConfirmation($this->output, $confirm_message, false)) {
            return false;
        }        
        return true;        
    }

    protected function saveSiteConfig()
    {
        global $app_ini_path;
        $app_ini_path = './haik-contents/config/haik.ini.php';
        require_once './haik-contents/lib/file.php';

        orgm_ini_write($this->siteConfig);

        $this->output->writeln('<info>Site initialization complete!</info>');
        return true;
    }

    protected function saveSiteCopy()
    {
        $this->output->writeln('<comment>Set site copy...</comment>');
        $this->output->writeln('');

        $front_page_file = './haik-contents/wiki/46726F6E7450616765.txt';
        $text = file_get_contents($front_page_file);
        $text = str_replace(['// MAIN_COPY', '// SUB_COPY'], ['# ' . $this->mainCopy, $this->subCopy], $text);
        file_put_contents($front_page_file, $text);
    }

}
