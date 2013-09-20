<?php
$config = array (
  'id' => 'contact',
  'description' => '問い合わせ用',
  'class' => 'form-horizontal',
  'deletable' => '0',
  'mail' => 
  array (
    'notify' => 
    array (
      'subject' => '',
      'body' => '*|ALL_POST_DATA|*',
      'to' => '',
    ),
    'reply' => 
    array (
      'subject' => '',
      'body' => '*|NAME|* 様
こんにちは。

お問い合わせ、ありがとうございました。
後ほどご連絡いたします。


以上です。

投稿内容：
*|ALL_POST_DATA|*
',
      'from_name' => '',
      'from_email' => '',
    ),
  ),
  'button' => '送信',
  'message' => '* お問い合わせの完了

こんにちは。
*|NAME|* 様

お問い合わせ、ありがとうございました。
確認メールをお送りしましたので、
ご確認ください。

以上です。
',
  'log' => '1',
  'post' => 
  array (
    'url' => '',
    'encode' => 'UTF-8',
    'data' => 
    array (
    ),
  ),
  'parts' => 
  array (
    'NAME' => 
    array (
      'type' => 'text',
      'label' => 'お名前',
      'help' => '',
      'value' => '',
      'size' => '',
      'placeholder' => '',
      'before' => '',
      'after' => '',
      'validation' => '',
      'required' => '1',
      'order' => '0',
      'id' => 'NAME',
    ),
    'EMAIL' => 
    array (
      'type' => 'email',
      'label' => 'メールアドレス',
      'help' => '',
      'value' => '',
      'size' => 'col-sm-6',
      'placeholder' => 'メールアドレス',
      'validation' => 'email',
      'required' => '1',
      'id' => 'EMAIL',
      'order' => '1',
    ),
    'MEMO' => 
    array (
      'type' => 'textarea',
      'label' => '内容',
      'value' => '',
      'size' => 'col-sm-8',
      'rows' => '10',
      'placeholder' => '',
      'required' => '1',
      'order' => '2',
      'id' => 'MEMO',
      'help' => '',
    ),
  ),
);
