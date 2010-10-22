<?php

class TestPage {
	protected $post = array('url' => 'http://www.hnsdev.gl/query', 'xml' => '');
	protected $out = '';
	
	public function processPost($post) {
		if (!@$post['url'] || !@$post['xml']) return;
		$this->post = $post;
		$ch = curl_init($post['url']);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post['xml']);
		$this->out = curl_exec($ch);
		curl_close($ch);
	}
	
	public function show() {
?>
<html><body>
<pre><?php echo(htmlspecialchars($this->out)); ?></pre>
<form method="post" action="">
<input type="text" size="50" name="url" value="<?php echo(htmlspecialchars($this->post['url'])); ?>" /><br />
<textarea rows="20" cols="50" name="xml"><?php echo(htmlspecialchars($this->post['xml'])); ?></textarea><br />
<input type="submit" />
</form>
</body></html>
<?php
	}
}

?>