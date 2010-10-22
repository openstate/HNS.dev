<?php

require_once('Controller.abstract.php');

class SandboxIndexController extends Controller {
	protected $url = SANDBOX_URL;
	
	public function __construct() {
		parent::__construct();
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

	public function indexAction() {
		if ($this->request->isPost() && strlen($post = @$this->request->getPost('xml'))) {
			$priv = openssl_get_privatekey($this->privKey);
			openssl_sign($post, $sig, $priv);
			openssl_free_key($priv);
			$sig = reset(unpack('H*', $sig));
			$get = array('user' => 'sandbox', 'key' => $sig);

			$ch = curl_init($this->url.'?'.http_build_query($get));
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			$out = curl_exec($ch);
			curl_close($ch);

			$_SESSION['sandbox'] = array('post' => $post, 'out' => $out);
			require_once('Wiki.class.php');
			$this->response->redirect(Wiki::inst()->redirect('Sandbox'));
			return;
		}
		$this->view->session = @$_SESSION['sandbox'];
		unset($_SESSION['sandbox']);
		$this->addPoFile('sandbox.po');
		$this->view->render('index.html');
	}
}

?>