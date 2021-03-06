<?php

class TestPage {
	protected $post = array('url' => 'http://www.hnsdev.gl/query', 'xml' => '');
	protected $out = '';
	protected $get = array();
	protected $privKey;
	
	public function __construct() {
		$this->post['url'] = 'http://'.$_SERVER['HTTP_HOST'].'/query';
		$this->privKey = <<<EOF
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCl0ExYgFjrI1up4m9xBOnfm9TMokTX6sCkOsDzzhwfS+ShYgMx
OZSryrHGAeAyGfYrVdRE5Zb6vch8JmJeaCrEhBcLdAq+xGURiFEBNsBePsuvs140
vevATiVtRAIOK0Mug7dqUGybDeczYheBBSd+WLPuaioEfr+/+/asiA34gQIDAQAB
AoGAUgpP2/IVDLJ/5fxdO0Q9GyAVF/KpsVM7YYaYdYjjLTD1vEusXKyqvJ0bfGbt
MJzbyfE7h6M5InLIQXUUcrWDgs/x2+xorQ0c6iw7NJJdoTOwqRDZS1n/wyn/Bg9u
lf52JAn6zAKbD4tfCleRQHshmd3uBC0tuPNvVtCa26sOmYECQQDWyaq9CaUjsHKo
iFWzIuSclpglLxkCe+npxUvW0PJ5J5i7mM+z7t1ESOhA7vMNMOsS1PlX8yWyRBMs
Ge8qD3T5AkEAxaEIfsGdFFmtUBHtpSq34eOEFSL24mW0/6KW4hw0/55qV9De8D9i
4d3pr5XqEUYJlu/J1EmcnVUdSHute7lpyQJBAMrbszdRSbf4aYJFKXPEC9jc3puX
7O4MrHMO1T7xH2FQBY+AlwLhIffhSAIz7DhUMGEb5terHLpOUzE+2USHTrkCQCP4
Iyuu4YAKsliYasBc/grG9gtCydx61m6QkRWmPJ8pngFNqsXfQ4gIc7fZeTibnrMy
AXH5099u1l2S5QhXvsECQAnQJi1IEGo/xHzVzjfv+ZGZ0NwpBTz03+Wr7Vopxv7F
8q0QhIF1EBvcP5DLFC1EKpCRu3pXMjCChTnGWgX96WI=
-----END RSA PRIVATE KEY-----
EOF;
	}
	
	public function processPost($post) {
		if (!@$post['url'] || !@$post['xml']) return;
		$this->post = $post;
		
		$priv = openssl_get_privatekey($this->privKey);
		openssl_sign($post['xml'], $sig, $priv);
		openssl_free_key($priv);
		$sig = reset(unpack('H*', $sig));
		
		$user = @$post['user'] == 'admin' ? 'admin' : 'test';
		$key = @$post['user'] == 'admin' ? $sig : 'USER_KEY';
		
		$this->get = array(
			'user' => $user,
			'key' => $key,
		);
		$ch = curl_init($post['url'].'?'.http_build_query($this->get));
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
<?php if($this->get): ?>
<pre>
user=<?php echo(htmlspecialchars($this->get['user'])); ?> 
key=<?php echo(htmlspecialchars($this->get['key'])); ?>
</pre>
<?php endif; ?>
<pre><?php echo(htmlspecialchars($this->out)); ?></pre>
<form method="post" action="">
<label><input type="radio" name="user" value="admin"<?php if (@$this->post['user'] == 'admin' || !@$this->post['user']): ?> checked="checked"<?php endif; ?>> Admin</label>
<label><input type="radio" name="user" value="test"<?php if (@$this->post['user'] == 'test'): ?> checked="checked"<?php endif; ?>> Test</label><br />
<input type="text" size="50" name="url" value="<?php echo(htmlspecialchars($this->post['url'])); ?>" /><br />
<textarea rows="20" cols="50" name="xml"><?php echo(htmlspecialchars($this->post['xml'])); ?></textarea><br />
<input type="submit" />
</form>
</body></html>
<?php
	}
}

?>