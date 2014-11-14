<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 10:15 PM
 */
include '../../vendor/autoload.php';

use ActiveAuth\TwoFactorAuthentication;

$activeAuth = new TwoFactorAuthentication(include '../../config/module.config.php');
$step = 1;
if (isset($_POST) && count($_POST)) {
    var_dump($_POST);
    switch ($_POST['step']) {
        case 1:
            $resultCodeTypes = $activeAuth->checkDevice('pavkatar@gmail.com', '24cc6b99');
            $step = 2;
            break;

        case 2:
            $resultCodeTypes = $activeAuth->checkDevice('pavkatar@gmail.com', '24cc6b99');
            $resultCode = $activeAuth->sendCode('pavkatar@gmail.com', '24cc6b99', $_POST['sendMethod']);
            $step = 3;
            break;
        case 3:
            $codeValidation = $activeAuth->verifyCode('pavkatar@gmail.com', '24cc6b99', $_POST['passcode']);
            var_dump($codeValidation);
            $step = 4;
            break;

    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="x-ua-compatible" content="ie=edge, chrome=1"/>
    <title>ActiveAuth</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="site-wrapper">
    <div class="site-wrapper-inner">
        <div class="cover-container">
            <div class="masthead clearfix">
                <div class="inner">
                    <h3 class="masthead-brand">ActiveAuth</h3>

                    <ul class="nav masthead-nav">
                        <li class="active">
                            <a href="http://bootsnipp.com/iframe/g6GWQ" target=
                            "_blank">View full screen</a>
                        </li>

                        <li>
                            <a href="#">Features</a>
                        </li>

                        <li>
                            <a href="#">Contact</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="inner cover">
                <div class="row">

                    <div class="col-xs-6">
                        <p class="lead">Register now for <span class="text-success">FREE</span></p>
                        <ul class="list-unstyled" style="line-height: 2">
                            <li><span class="fa fa-check text-success"></span> See all your orders</li>
                            <li><span class="fa fa-check text-success"></span> Fast re-order</li>
                            <li><span class="fa fa-check text-success"></span> Save your favorites</li>
                            <li><span class="fa fa-check text-success"></span> Fast checkout</li>
                            <li><span class="fa fa-check text-success"></span> Get a gift
                                <small>(only new customers)</small>
                            </li>
                            <li><a href="/read-more/"><u>Read more</u></a></li>
                        </ul>
                        <p><a href="/new-customer/" class="btn btn-info btn-block">Yes please, register now!</a></p>
                    </div>
                    <div class="col-xs-6">
                        <div class="well">
                            <form id="loginForm" method="POST" action="" novalidate="novalidate">

                                <?php
                                if ($step == 1):
                                    ?>

                                    <div class="form-group">
                                        <label for="username" class="control-label">Email</label>
                                        <input type="text" class="form-control" id="email" name="email" value=""
                                               required=""
                                               title="Please enter you username" placeholder="example@gmail.com">
                                        <span class="help-block"></span>
                                    </div>

                                    <div id="loginErrorMsg" class="alert alert-error hide">Wrong username og password
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="remember" id="remember"> Remember login
                                        </label>

                                    </div>
                                    <button type="submit" class="btn btn-success btn-block">Login</button>
                                    <a href="/forgot/" class="btn btn-default btn-block">Help to login</a>

                                <?php
                                elseif ($step == 2):?>
                                    <label for="username" class="control-label">Choose your auth type: </label>

                                    <div class="btn-group btn-group-justified" role="group" aria-label="...">

                                        <?php
                                        if (isset($resultCodeTypes) && is_array($resultCodeTypes)):
                                            foreach ($resultCodeTypes['methods'] as $method => $status):
                                                ?>
                                                <div class="btn-group" role="group">
                                                    <button type="submit" name="sendMethod" class="btn btn-info"
                                                            value="<?= $method; ?>"><?= $method; ?></button>
                                                </div>
                                            <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                <?php
                                elseif ($step == 3):
                                    ?>
                                    <?php
                                    if (isset($resultCode['codestarts'])):
                                        ?>
                                        <label class="control-label">You will receive long string, please write the
                                            code which starts with: <?= $resultCode['codestarts']; ?>: </label>
                                    <?php
                                    endif;
                                    ?>
                                    <br>
                                    <div class="form-group">
                                        <label for="passcode" class="control-label">Passcode</label>
                                        <input type="text" class="form-control" id="passcode" name="passcode"
                                               value=""
                                               required=""
                                               title="Please enter the passcode you receive on your phone"
                                               placeholder="Passcode..">
                                        <span class="help-block"></span>
                                    </div>


                                    <button type="submit" class="btn btn-success btn-block">Verify</button>

                                <?php
                                elseif ($step == 4):
                                    ?>
                                    <br>
                                    <?php
                                    if (isset($codeValidation['error'])):
                                        ?>
                                        <div class="alert alert-danger" role="alert"><?= $codeValidation['error']; ?>:
                                        </div>
                                    <?php
                                    endif;
                                    ?>


                                <?php
                                endif;
                                ?>
                                <input type="hidden" name="step" value="<?= $step; ?>">
                            </form>
                        </div>
                    </div>

                </div>
            </div>

            <div class="mastfoot">
                <div class="inner">
                    <!-- Validation -->
                    <p>© 2014 ApiHawk ~ <a href="http://getbootstrap.com/">Bootstrap</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
</body>
</html>