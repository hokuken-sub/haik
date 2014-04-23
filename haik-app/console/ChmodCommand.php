<?php namespace Hokuken\Haik\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChmodCommand extends Command {

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    protected $modeMask = 0777;

    protected function configure()
    {
        $this->setName('chmod')
             ->setDescription('Change mode haik files')
             ->addOption(
                'mask',
                null,
                InputOption::VALUE_REQUIRED,
                'Set file mode modifying mask.',
                0777
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

		$output->writeln("<info>chmod: starting...</info>\n");

        if ($input->hasOption('mask'))
        {
            $this->modeMask = intval($input->getOption('mask'), 0);
            $mask = '0' . decoct($this->modeMask);
            $output->writeln('<comment>chmod: set mode mask: '. $mask .'</comment>');
            $output->writeln('');
        }

        return $this->chmodFiles();

    }

    protected function getPermissions()
    {
        return array(
            './haik-contents/config' => 0777,
            './haik-contents/backup' => 0777,
            './haik-contents/cache'  => 0777,
            './haik-contents/wiki'   => 0777,
            './haik-contents/diff'   => 0777,
            './haik-contents/meta'   => 0777,
            './haik-contents/theme'   => 0777,
            './haik-contents/upload' => 0777,
            './haik-contents/css'    => 0755,
            './haik-contents/img'    => 0755,
            './haik-contents/js'     => 0755,
            './haik-contents/lib'    => 0755,
            './haik-contents/plugin' => 0755,
        );
    }

    protected function chmodFiles()
    {
        $perms = $this->getPermissions();

        foreach ($perms as $dir => $perm)
        {
            $this->chmodRecursive($dir, $perm);
        }

        $this->output->writeln('');
        $this->output->writeln('<info>chmod: Successfully completed!</info>');
    }

    protected function chmodRecursive($dir, $perm)
    {
        $this->output->writeln("<comment>changing {$dir}/</comment>");
        $org_perm = $perm;
        $perm = $this->modeMask & $perm;
        chmod($dir, $perm);
        if ($org_perm === 0777)
        {
            // ディレクトリ内のファイルは、666にする
            // .htaccess は 644
            $files = scandir($dir);
            foreach ($files as $file)
            {
                if (in_array($file, ['.', '..', '.git'])) continue;
                $path = rtrim($dir, '/') . '/' . $file;
                if (is_dir($path))
                {
                    chmod($path, $perm);
                    $this->chmodRecursive($path, $org_perm);
                }
                else
                {
                    if (basename($path) === '.htaccess')
                    {
                        $htaccess_perm = $this->modeMask & 0644;
                        chmod($path, $htaccess_perm);
                    }
                    else
                    {
                        $file_perm = $this->modeMask & 0666;
                        chmod($path, $file_perm);
                    }
                }
            }
        
        }
        
    }

}
