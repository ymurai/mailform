<?php

require_once ('Qdmail.php');
mb_language('ja');
session_start();

class Form {

	public function __construct($config = array()) {

    $config += array(
			'id' => 'form',
			'class' => 'form',
			'submit_confirm' => '確認する',
			'submit_finish' => 'これで送信',
			'submit_back' => '修正する',
			'confirm' => false,
			'ajax' => true,
			'redirect' => false,
		);

		$this->config = $config;

	}

	var $format = array();
	var $sendmail = array();
	var $autoreply = array();

	var $message = array(
			'novalue' => 'なにも入力されていません',
			'finish' => 'ご入力ありがとうございました。',
		);

	function set($name, $required = null) {

		if ($name == 'submitaction') { return; }

		$this->format[$name] = array();
		if ($required) { $this->format[$name]['required'] = true; }
		$this->meta = $name;
		return $this;
	
	}

	function type($type) {
		if (!array_search($type, array('text', 'password', 'checkbox', 'radio',
			'file', 'hidden', 'select', 'textarea') )) {
			$type = 'text';
		}
		$name = $this->meta;
		$this->format[$name]['type'] = $type;
		return $this;
	}

	function label($label) {
		$name = $this->meta;
		$this->format[$name]['label'] = $label;
		return $this;
	}

	function choices($array) {
		$name = $this->meta;
		$this->format[$name]['choices'] = $array;
		return $this;
	}

	function required($flag) {
		$name = $this->meta;
		$this->format[$name]['required'] = $flag;
		return $this;
	}

	function value($value) {
		$name = $this->meta;
		$this->format[$name]['value'] = $value;
		return $this;
	}

	function html() {

		$post = $this->SanitizePOST($_POST);

		switch ($post['submitaction']) {
			case $this->config['submit_confirm']:
				$caution = $this->validation($post);
				if ($caution) {
					$this->drawForm($post, $caution);
				} else {
					$this->drawConfirm($post);
				}
				break;

			case $this->config['submit_finish']:
				$caution = $this->validation($post);
				if ($caution) {
					$this->drawForm($post, $caution);
				} else {
					$this->finishAction($post);
				}
				break;
			
			case $this->config['submit_back']:
			default:
				$this->drawForm($post);
				break;
		}
		
		return;

	}

	function drawForm($post = null, $caution = null) {

		echo '<form action=""  method="post" enctype="multipart/form-data"' . 
		' id ="' .$this->config['id']. '" class="' .$this->config['class']. '">';

		foreach ($this->format as $name => $parts) {

			switch ($parts['type']) {
				case 'text':
				case 'password':
					echo '<label for="' .$name. '" class="' .$name. '">' .$parts['label']. '</label>' ;
					echo '<input type="' .$parts['type']. '" name="' .$name. '" class="' .$name. '" value="' .$post[$name]. '">';
					if ($caution[$name]) { echo '<span class="error">' .$caution[$name]. '</span>';}
					echo '<br>';
					break;

				case 'checkbox':
				case 'radio':
					echo '<span class="' .$name. ' label">' .$parts['label']. '</span>' ;
					foreach ($parts['choices'] as $value) {
						echo '<label><input type="' .$parts['type']. '" name="' .$name. '" value="' .$value. '"  class="' .$name. '"';
						if ($value === $post[$name]) { echo ' checked';}
						echo '>' .$value. '</label>';
					}
					echo '<br>';
					break;

				case 'hidden':
					echo '<input type="' .$parts['type']. '" name="' .$name. '" value="' .$parts['value']. '">';
					break;
				
				case 'select':
					echo '<label for="' .$name. '" class="' .$name. '">' .$parts['label']. '</label>' ;
					echo '<select name="' .$name. '">';
					foreach ($parts['choices'] as $value) {
						echo '<option value="' .$value. '"';
						if ($value === $post[$name]) { echo ' selected';}
						echo '>' .$value. '</option>';
					}
					echo '</select><br>';
					break;

				case 'textarea':
					$placeholder = $post[$name] ?: $parts['placeholder'];
					echo '<label for="' .$name. '" class="' .$name. '">' .$parts['label']. '</label>' ;
					echo '<textarea name="' .$name. '" class="' .$name. '">' .$placeholder. '</textarea><br>';
					if ($caution[$name]) { echo '<span class="error">' .$caution[$name]. '</span>';}
					echo '<br>';
					break;
				
			}

		}

		$action = ($this->config['confirm']) ? $this->config['submit_confirm'] : $this->config['submit_finish'];
		echo '<input type="submit" name="submitaction" value="' .$action. '" class="submit">';
		echo '</form>';

    return;

	}

	function drawConfirm($post) {

		echo '<form action=""  method="post" enctype="multipart/form-data"' . 
		' id ="' .$this->config['id']. '" class="' .$this->config['class']. ' confirm">';

		foreach ($this->format as $name => $parts) {

			echo '<input type="hidden" name="' .$name. '" value="' .$post[$name]. '">';
			echo '<span class="' .$name. ' label">' .$parts['label']. '</span>';
			if ($parts['type'] == 'password') {}
			$value = ($parts['type'] != 'password') ? $post[$name] : '********';
			echo '<span class="' .$name. ' value">' .$value. '</span><br>';

		}

		echo '<input type="submit" name="submitaction" value="' .$this->config['submit_finish']. '" class="submit">';
		echo '<input type="submit" name="submitaction" value="' .$this->config['submit_back']. '" class="submit">';

		echo '</form>';

		return;

	}

	function finishAction($post) {

		if ($this->sendmail['switch']) {
			$this->sendmail['body'] = $this->tag($post, $this->sendmail['body']);
			$this->send($this->sendmail);
		}
		if ($this->autoreply['switch']) {
			$this->autoreply['body'] = $this->tag($post, $this->autoreply['body']);
			$this->autoreply['to_mail'] = $post['mail'];
			$this->send($this->autoreply);
		}

		if ($this->config['redirect']) {
			header( 'Location: ' .$this->config['redirect'] );
			exit;
		}

		echo '<div class="finish"><p>' .$this->message['finish']. '</p></div>';
		return;

	}

	function send($sendmail) {

		$mail = new Qdmail();
		$mail->to( $sendmail['to_mail'], $sendmail['to'] );
		$mail->subject( $sendmail['subject'] );
		$mail->text( $sendmail['body'] );
		$mail->from( $sendmail['from_mail'], $sendmail['from'] );
		$mail->send();

		return;

	}

	function validation($post) {

		foreach ($this->format as $name => $parts) {
			if ($parts['required'] && !$post[$name]) {
				$caution[$name] = 'novalue';
			}
		}

		return $caution;

	}

	function sendmail($array) {
		$this->sendmail = $array;
		return;
	}

	function autoreply($array) {
		$this->autoreply = $array;
		return;
	}

	function tag($tagList,$text) {

		foreach($tagList as $key => $value) {
			$tag = '##'.$key.'##';
			$text = str_replace($tag, $value, $text);
		}
		return $text;

	}

	function Sanitize($str) {
		$str = preg_replace("{<!--.*-->}", "", $str);
		$str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
		return $str;
	}

	function SanitizePOST($post) {
	  foreach ($post as $key => $value) {
	    if (!is_array($value)) {
	      $result[$key] = $this->Sanitize($value);
	    } else {
	     foreach ($value as $select => $check) {
	       $result[$key][$select] = $this->Sanitize($check);
	     }
	    }
	  }
	  return $result;
	}

}