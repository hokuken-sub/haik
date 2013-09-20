<?php
/**
 *   Simple Mail 
 *   -------------------------------------------
 *   /lib/simplemail.php
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 
 *   modified : 13/04/22
 *   
 *   メール送信クラス。
 *   日本語環境を主軸とするメール送信を行う。
 *   テンプレート機能を持つ。
 *   
 *   Usage :
 *   $sm = new SimpleMail();
 *   $sm->set_params('送信者名', 'sender@hoge.com');  //Fromやらがセットされる
 *   $sm->set_to('宛先名', 'toadr@example.com');
 *   $body = 'hoghoge...';
 *
 *   $sm->add_attaches('data/profile.jpg');
 *
 *   $sm->send($body);
 *   
 */

if (file_exists(dirname(__FILE__) . '/qdmail.php'))
{
	require_once(dirname(__FILE__) . '/qdmail.php');
}
if (file_exists(dirname(__FILE__) . '/qdsmtp.php'))
{
	require_once(dirname(__FILE__) . '/qdsmtp.php');
}

class SimpleMail
{

	public $language = '';
	public $encoding = 'UTF-8';
	public $mail_encode = 'ISO-2022-JP';
	
	// backend kinds: mail, smtp_auth, pop_before, smtp
	public $backend = '';
	
	//SMTP
	public $smtp_server;
	public $smtp_port = 25;
	public $smtp_auth;
	public $mail_userid;
	public $mail_passwd;

	//POP before SMTP
	public $pop_server;
	public $pop_port = 110;
	public $pop_auth_use_apop = TRUE;
	public $must_use_apop     = FALSE;
	
	public $from = array('name' => '', 'email' => '');
	public $return_path = '';
	public $reply_to = '';
	public $x_mailer = '';
	public $to = array('name' => '', 'email' => '');
	public $subject = '';

	public $attaches = array();
	public $boundary = '__ORGM_MAIL_BOUNDARY__';

	public $merge_tags = array();

	public $error = '';

	
	public function SimpleMail()
	{
	
		$this->boundary .= time();
		$this->x_mailer = APP_NAME . ' Mail Sender';
	
	}
	
	public function set_encode($mail_encode)
	{
		$this->mail_encode = $mail_encode;
	}

	/**
	 * バックエンドプログラムを指定する。
	 * 引数なしで自動判別。
	 */	
	public function set_backend($backend = '')
	{
		if ($backend === '')
		{
			if ($this->smtp_server !== '')
			{
				if ($this->smtp_auth)
				{
					if ($this->pop_server !== '')
						$this->backend = 'pop_before';
					else
						$this->backend = 'smtp_auth';
				}
				else
				{
					$this->backend = 'smtp';
				}
			}
			else
			{
				$this->backend = 'mail';
			}
		}
		else
		{
			$this->backend = $backend;
		}
	}
	
	public function set_smtp_server($smtp_server)
	{
		$this->smtp_server = $smtp_server;
		if (preg_match('/^(.+):(\d+)$/', $smtp_server, $mts))
		{
			$this->smtp_server = $mts[1];
			$this->smtp_port = $mts[2];
		}
		
	}
	
	public function set_smtp_auth($available, $mail_userid, $mail_passwd, $pop_server = '')
	{
		$this->smtp_auth = $available;
		
		if ($available)
		{
			$this->pop_server = $pop_server;
			if (preg_match('/^(.+):(\d+)$/', $pop_server, $mts))
			{
				$this->pop_server = $mts[1];
				$this->pop_port = $mts[2];
			}

			$this->mail_userid = $mail_userid;
			$this->mail_passwd = $mail_passwd;
		}
	}
	
	public function set_params($name, $email, $x_mailer = '')
	{
		$this->from = array(
			'name'  => $name,
			'email' => $email
		);
		$this->return_path = $email;
		$this->reply_to    = $email;
		
		if ($x_mailer) $this->x_mailer = $x_mailer;
		
	}
	
	public function set_from($name, $email)
	{
		$this->set_params($name, $email);
	}
	
	public function set_to($name, $email)
	{
		$this->to = array('name'=>$name, 'email'=>$email);
	}
	
	public function set_subject($subject)
	{
		$this->subject = $subject;
	}
	
	public function add_attaches($fname, $path = '')
	{
	
		if ($path == '')
		{
			$path = $fname;
			$fname = basename($fname);
		}
	
		if( ! file_exists($path))
		{
			die($path . ' is not exists.');
			exit;
		}
		
		if (isset($this->attaches[$fname]))
		{
			$this->attaches[$fname.'-1'] = $path;
		}
		else
		{
			$this->attaches[$fname] = $path;
		}
	}
		
	public function send($body, $merge_tags = NULL)
	{
		$this->set_backend($this->backend);
		
		//必ず、多言語設定をUTF-8と設定する
		mb_language($this->language);
		mb_internal_encoding($this->encoding);
		
		$from_name = $this->from['name'];
		$from_adr = $this->from['email'];
		$rpath = ($this->return_path=='') ? $from_adr : $this->return_path;
		$repto = ($this->reply_to=='') ? $from_adr : $this->reply_to;
		$xmailer = ($this->x_mailer=='') ? "PHP/" . phpversion() : $this->x_mailer;
		$to_name = $this->to['name'];
		$to_adr = $this->to['email'];

		$en_from = $this->mime($from_name).' <'.$from_adr.'>';

		$body = str_replace("\r", "", $body);
		$body = mb_convert_kana($body, "KV");

		if ($merge_tags !== NULL)
		{
			$this->set_merge_tags($merge_tags);
		}
		if ($this->merge_tags)
		{
			$body = $this->replace_merge_tags($body);
			$this->subject = $this->replace_merge_tags($this->subject);
		}

		$body = mb_convert_encoding($body, $this->mail_encode, $this->encoding);


		$encoding = ($this->mail_encode === 'ISO-2022-JP') ? '7bit' : 'base64';
		//添付ファイルあり、なし
		$add_msg = '';
		if (count($this->attaches) && $this->backend !== 'smtp_auth')
		{
			$header_content_type = 'Content-Type: multipart/mixed;boundary="'.$this->boundary.'"';
			
			$cnt = 0;
			
			$body = '--'.$this->boundary.'
Content-Type: text/plain; charset='. $this->mail_encode .'
Content-Transfer-Encoding: '.$encoding.'

'.$body."\n";

			foreach($this->attaches as $fname=>$path){
				$body .= '--'.$this->boundary."\n";
				$body .= 'Content-Type: '.$this->get_mimetype($fname).'; name="'.$this->mime($fname).'"'."\n";
				$body .= 'Content-Transfer-Encoding: base64'."\n";
				$body .= 'Content-Disposition: attachment; filename="'.$this->mime($fname).'"'."\n";
				$body .= 'Content-ID: <'.$cnt.'>'."\n";
				$body .= "\n";
				$body .= chunk_split(base64_encode( file_get_contents($path) ));
				
				$cnt++;
			}
			$body .= '--'.$this->boundary."--\n";
		}
		else{
			$header_content_type = 'Content-Type: text/plain;charset='. $this->mail_encode . "\n";
			$header_content_type .= 'Content-Transfer-Encoding: ' . $encoding;
		}

				
		$headers =  "MIME-Version: 1.0 \n".
					"From: {$en_from}\n".
					"Reply-To: {$repto}"."\n".
					"X-Mailer: {$xmailer}\n".
					$header_content_type."\n";
					
		$subject = $this->mime( ($this->subject=='') ? __('件名なし') : $this->subject );
		
		$sendmail_params  = "-f $from_adr";


		// --------------------------------------
		// メール送信時のオプション
		if ($to_name=='')
		{
			$en_to = $to_adr;
		}
		else
		{
			$en_to = $this->mime($to_name).' <'.$to_adr.'>';
		}


		if ($this->backend === 'smtp' OR $this->backend === 'pop_before')
		{
			if ($this->backend === 'pop_before')
			{
				$result = $this->pop_before_smtp();
				if ($result !== TRUE)
				{
					$this->error = 'POP before SMTP failed.<br>' . $result;
					return FALSE;
				}
			}
			ini_set('smtp_port', $this->smtp_port);
			ini_set('SMTP', $this->smtp_server);
		}
		else if ($this->backend === 'smtp_auth')
		{

			$mail = new Qdmail();
			
			$mail->smtp(TRUE);
			$mail->charset($this->mail_encode, $encoding);
			
			$param = array(
				'host' => $this->smtp_server,
				'port' => $this->smtp_port,
				'from' => $this->return_path,
				'protocol' => 'SMTP_AUTH',
				'user' => $this->mail_userid,
				'pass' => $this->mail_passwd,
			);
			$mail->smtpServer($param);
			
			$mail->to($this->to['email'], $this->to['name']);
			$mail->subject($this->subject);
			$mail->from($this->from['email'], $this->from['name']);
			$mail->text($body);
			
			//attach files
			$attaches = array();
			foreach ($this->attaches as $fname => $path)
			{
				$attaches[] = array($path, $fname);
			}
			$mail->attach($attaches);
			
			$return_flag = $mail->send();
			
			return $return_flag;
		}

		// --------------------------------------
		// メール送信(safe_modeによる切り替え)
		if (ini_get('safe_mode'))
		{
			return mail($en_to, $subject, $body, $headers);
		}
		else
		{
			return mail($en_to, $subject, $body, $headers, $sendmail_params);
		}
	}
	

	function set_merge_tags($key, $value = NULL)
	{
		if ($value === NULL && is_array($key))
			$data = $key;
		else if (is_string($key))
			$data = array($key => $value);
		else
			$data = array();
		
		if ($data && array_keys($data) != range(0, count($data)))
		{
			$this->merge_tags = array_merge($this->merge_tags, $data);
		}
	}

	/**
	 * 受け取った文字列をmerge_tags に従って置換する。
	 * 置換ルールは *|KEY|* 
	 */
	function replace_merge_tags($text)
	{
		$merge_tags = array();
		foreach($this->merge_tags as $key => $value)
		{
			$tag = '*|'. strtoupper($key) .'|*';
			$merge_tags[$tag] = $value;
		}
	
		$ptns = array_keys($merge_tags);
		$rpls = array_values($merge_tags);
		$text = str_replace($ptns, $rpls, $text);
		
		return $text;
	}


	public function pop_before_smtp()
	{
	
		// Connect
		$errno = 0; $errstr = '';
		$fp = @fsockopen($this->pop_server, $this->pop_port, $errno, $errstr, 30);
		if (! $fp) return ('pop_before_smtp(): ' . $errstr . ' (' . $errno . ')');
	
		// Greeting message from server, may include <challenge-string> of APOP
		$message = fgets($fp, 1024); // 512byte max
		if (! preg_match('/^\+OK/', $message)) {
			fclose($fp);
			return ('pop_before_smtp(): Greeting message seems invalid');
		}
	
		$challenge = array();
		if ($this->pop_auth_use_apop &&
		   (preg_match('/<.*>/', $message, $challenge) || $this->must_use_apop)) {
			$method = 'APOP'; // APOP auth
			if (! isset($challenge[0])) {
				$response = md5(time()); // Someting worthless but variable
			} else {
				$response = md5($challenge[0] . $this->mail_passwd);
			}
			fputs($fp, 'APOP ' . $this->mail_userid . ' ' . $response . "\r\n");
		} else {
			$method = 'POP'; // POP auth
			fputs($fp, 'USER ' . $this->mail_userid . "\r\n");
			$message = fgets($fp, 1024); // 512byte max
			if (! preg_match('/^\+OK/', $message)) {
				fclose($fp);
				return ('pop_before_smtp(): USER seems invalid');
			}
			fputs($fp, 'PASS ' . $this->mail_passwd . "\r\n");
		}
	
		$result = fgets($fp, 1024); // 512byte max, auth result
		$auth   = preg_match('/^\+OK/', $result);
	
		if ($auth) {
			fputs($fp, 'STAT' . "\r\n"); // STAT, trigger SMTP relay!
			$message = fgets($fp, 1024); // 512byte max
		}
	
		// Disconnect anyway
		fputs($fp, 'QUIT' . "\r\n");
		$message = fgets($fp, 1024); // 512byte max, last '+OK'
		fclose($fp);
	
		if (! $auth) {
			return ('pop_before_smtp(): ' . $method . ' authentication failed');
		} else {
			return TRUE;	// Success
		}
	}

	/**
	 *   内部エンコードを変えて、mb_encode_mimeheader() をかける
	 *   長い差し出し人名などに対応（長すぎると消える）
	 */
	function mime($str = '', $mail_encode='')
	{
		$mail_encode = ($mail_encode === '') ? $this->mail_encode : $mail_encode;
		mb_internal_encoding($mail_encode);
		$subject = mb_encode_mimeheader( mb_convert_encoding($str, $mail_encode, $this->encoding), $mail_encode, 'B');
		mb_internal_encoding($this->encoding);
		return $subject;
	}
	
	function get_mimetype($fname)
	{
		$ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
		
		switch($ext){
		
			case 'txt' : return 'text/plain';
			case 'csv' : return 'text/csv';
			case 'html':
			case 'htm' : return 'text/html';

			//
			case 'pdf' : return 'application/pdf';
			case 'css' : return 'text/css';
			case 'js'  : return 'text/javascript';
			
			//image
			case 'jpg' :
			case 'jpeg': return 'image/jpeg';
			case 'png' : return 'image/png';
			case 'gif' : return 'image/gif';
			case 'bmp' : return 'image/bmp';
			
			//av
			case 'mp3' : return 'audio/mpeg';
			case 'm4a' : return 'audio/mp4';
			case 'wav' : return 'audio/x-wav';
			case 'mpg' :
			case 'mpeg': return 'video/mpeg';
			case 'wmv' : return 'video/x-ms-wmv';
			case 'swf' : return 'application/x-shockwave-flash';
			
			//archives
			case 'zip' : return 'application/zip';
			case 'lha' : 
			case 'lzh' : return 'application/x-lzh';
			case 'tar' :
			case 'tgz' :
			case 'gz'  : return 'application/x-tar';
			
			
			//office files
			case 'doc' :
			case 'dot' : return 'application/msword';
			case 'docx': return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
			case 'xls' : 
			case 'xlt' : 
			case 'xla' : return 'application/vnd.ms-excel';
			case 'xlsx': return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			case 'ppt' : 
			case 'pot' : 
			case 'pps' :
			case 'ppa' : return 'application/vnd.ms-powerpoint';
			case 'pptx': return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
			
		}
		
		return 'application/octet-stream';
		
		
	}
}


/* End of file simplemail.php */
/* Location: /haik-contents/lib/simplemail.php */