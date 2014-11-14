ActiveAuth_PHP
==============

**ActiveAuth PHP** - two-factor authentication for PHP web applications

This package allows a web developer to add ActiveAuths's two-factor authentication to any web login form.

Package:

* `js` - ActiveAuth javascript library, to be hosted by your webserver.
* `ActiveAuth.php` - ActiveAuth PHP SDK to be integrated with your web application

## Usage

### 0. Befor you start generate application key:

Your application key (or akey) is a string that you should generate and keep secret from ActiveAuth. It should be 40 characters long and stored alongside your integration key, secret key, integration account and integration server in configuration. This should be done once.

You can generate a random string in Perl with:

```
perl -e 'print join "", map { sprintf "%08X", rand(0xffffffff) } 1 .. 5'
```

### 1. Sign your request

After you perform primary authentication (username and password), you should prepare signature for the secondary authentication process by calling `ActiveAut::sign` method:

```
$activeAuth = new AzureCoastAuthenticator();
$secret = $activeAuth->sing($username, $ikey, $skey, $akey);
```

Where:

* `$username` is the e-mail of the already first-step-authenticated account
* `$ikey` is the integration key you get from ActiveAuth's control panel
* `$skey` is the server key you get from ActiveAuth's control panel
* `$akey` which is the application key you generated

### 2. Show the IFRAME

After generating the signed request, your server should now display an IFRAME used for secondary authentication.

ActiveAuth’s JavaScript handles the setup and communication between the IFRAME, the user, and your server. All you need to do is include a short snippet of JavaScript in the page:

```
<iframe src="" id="acaframe"></iframe>
<script type="text/javascript">
  var ACASecret = '$secret';
  var ACAServer = '$server';
  var ACAAccount = '$iaccount';
  var ACAAction = '';
</script>
<script type="text/javascript" src="js/activeauth.js"></script>
```

Where:

* `$secret` is the signature generated in the previous step
* `$server` is the address of ActiveAuth server (`activeauth.me`)
* `$iaccount` is the e-mail of the integration account, whch owns the integration (NOT the authenticated user) in the ActiveAuth service control panel.
* In the `ACAAction` variable you can specify the FORM action to POST to where the second-step authentication is to be verified.

### 4. Verify the response

After the user authenticates (e.g. via mobile push, phone call, SMS passcode, etc.) the IFRAME will generate a signed response and send it back to the JavaScript which will POST to `ACAAction` specified in the previous step. Your server-side code should then call `ActiveAuth::verify()` to verify that the signed response is legitimate:

```
$activeAuth = new AzureCoastAuthenticator();
$response = $_POST['2fa-verify'];
$status = $activeAuth->verify($response, $skey, $akey);
```

Where:

* `$response` is the signed response received from the ActiveAuth server
* `$skey` is the server key you get from ActiveAuth's control panel
* `$akey` which is the application key you generated

If sucessfully authenticated the returned value (`$status`) should be the e-mail of the authenticated user. Otherwise the method will return `undef`. After getting user's e-mail, you can create your application session for the sepcified user.



