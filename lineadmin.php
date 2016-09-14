<?php
ini_set('display_errors', true);
error_reporting(E_ALL);

/**
 * Script file to work with WordPress users via PHP and GET requests
 * 
 * Requirements:
 * PHP 5.x
 * WordPress 2.8
 * 
 * get_user_by          @since 2.8
 * wp_set_password      @since 2.5
 * wp_delete_user       @since 2.0
 * WP_User::set_role    @since 2.0
 * wp_dropdown_roles    @since 2.1
 * esc_attr             @since 2.8
 * esc_html             @since 2.8
 * get_bloginfo         @since 0.71
 * wp_create_user       @since 2.0
 * username_exists      @since 2.0
 * email_exists         @since 2.1
 * 
 * @author Alex Chizhov <ac@alexchizhov.com> 
 * @author LINE Internet Development (c) <sales@line-corp.ru> 
 */
require_once('wp-blog-header.php');

$version = get_bloginfo('version');

if ($version < 3.1)
    require_once('wp-includes/registration.php');

require_once('wp-admin/includes/template.php');
require_once('wp-admin/includes/user.php');
?>

<!DOCTYPE html>
<html>
    <head>

        <title>LINE Internet Development | Custom WordPress users administration</title>

        <style>
            table{
                font:12px Arial, sans-serif;
                border-collapse:collapse;
                width:100%;
            }

            table thead tr td{
                font-weight:bold;
                text-align:center;
            }

            table td{
                border:1px solid #999;
                padding:15px;
            }

            table tr:nth-child(2n) td{
                background:#efefef;
            }

            table tbody tr:hover td{
                background:yellow;
            }

            table a{
                font-size:10px;
                color:red;
            }
        </style>


    </head>
    <body>

        <h2>Create new user</h2>
        <form action="<?= esc_attr($_SERVER['PHP_SELF']) ?>" method="post">

            <input type="text" name="newusername" placeholder="Login">
            <input type="text" name="newpassword" placeholder="Password">
            <input type="text" name="newemail" placeholder="Email">
            <select name="newrole">
                <?php wp_dropdown_roles(); ?>
            </select>

            <input type="hidden" name="action" value="usercreate">

            <input type="submit" value="Create user">

        </form>


        <?php
        if (isset($_POST) && !empty($_POST)) {

            // CREATE USER
            if (
                    isset($_POST['newusername']) &&
                    isset($_POST['newpassword']) &&
                    isset($_POST['newemail']) &&
                    isset($_POST['newrole']) &&
                    isset($_POST['action']) &&
                    $_POST['action'] === 'usercreate'
            ) {

                if (!username_exists($_POST['newusername']) && !email_exists($_POST['newpassword'])) {

                    echo '<p>Creating new user</p>';

                    $user_id = wp_create_user($_POST['newusername'], $_POST['newpassword'], $_POST['newemail']);

                    if (is_int($user_id)) {

                        echo '<p>User <strong>' . esc_html($_POST['newusername']) . '</strong> created. You can now loggin using the set password: <strong>' . esc_html($_POST['newpassword']) . '</strong></p>';

                        $wp_user_object = new WP_User($user_id);
                        $wp_user_object->set_role($_POST['newrole']);

                        echo '<p>User role <strong>' . esc_html($_POST['newrole']) . '</strong> is set for user: <strong>' . $wp_user_object->user_login . '</strong></p>';
                    } else {

                        echo '<p>WARNING: Something went wrong while creating new user. Please try reloading the page.</p>';
                    }
                } else {

                    echo '<p>User already exists</p>';
                }
            }

            // CHANGE ROLE
            if (
                    isset($_POST['action']) &&
                    $_POST['action'] === 'changerole' &&
                    isset($_POST['changerole']) &&
                    isset($_POST['userid'])
            ) {

                echo '<h3>CHANGING USER ROLE</h3>';

                $UserCR = get_user_by('id', $_POST['userid']);

                if ($UserCR instanceof WP_User && isset($UserCR->ID) && is_int($UserCR->ID)) {

                    echo "<p>Role successfully changed for User with ID: <strong>" . intval($UserCR->ID) . "</strong> from <strong>" . implode(', ', $UserCR->roles) . "</strong> to <strong>" . esc_html($_POST['changerole']) . "</strong></p>";

                    $changeResult = $UserCR->set_role($_POST['changerole']);
                } else {

                    echo '<p>WARNING: Something went wrong. Role wasn\'t changed.</p>';
                }
            }

            // CHANGE PASSWORD
            if (
                    isset($_POST['action']) &&
                    $_POST['action'] === 'changepassword' &&
                    isset($_POST['userid']) &&
                    isset($_POST['password'])
            ) {

                echo '<h3>CHANGING PASSWORD</h3>';

                $UserCP = get_user_by('id', $_POST['userid']);

                if ($UserCP instanceof WP_User) {

                    wp_set_password($_POST['password'], $_POST['userid']);

                    echo '<p>Password for User with ID <strong>' . intval($_POST['userid']) . '</strong> was successfully changed! You can now login using your new password: <strong>' . esc_html($_POST['password']) . '</strong></p>';
                } else {

                    echo '<p>User with ID <strong>' . intval($_POST['userid']) . '</strong> doesn\'t exist.</p>';
                }
            }
        }

        // DELETE USER
        if (
                isset($_GET) &&
                !empty($_GET) &&
                isset($_GET['action']) &&
                $_GET['action'] === 'deleteuser' &&
                isset($_GET['userid'])
        ) {

            echo '<h3>DELETING USER</h3>';

            $UserDU = get_user_by('id', $_GET['userid']);

            if ($UserDU instanceof WP_User) {

                $deleted = wp_delete_user($UserDU->ID);

                if ($deleted) {

                    echo '<p>User with ID <strong>' . intval($_GET['userid']) . '</strong> was successfully deleted</p>';
                } else {

                    echo '<p>WARNING: Something went wrong while deleting User with ID <strong>' . intval($_GET['userid']) . '</strong></p>';
                }
            } else {

                echo '<p>User with ID <strong>' . intval($_GET['userid']) . '</strong> doesn\'t exist</p>';
            }
        }
        ?>


        <h2>Users list</h2>

        <table>
            <thead>
                <tr>
                    <td style="width:50px;">User ID</td>
                    <td>Login</td>
                    <td>Email</td>
                    <td>Display Name</td>
                    <td>Registration date</td>
                    <td>Role(s)</td>
                    <td>Password</td>
                    <td>Action</td>
                </tr>
            </thead>

            <tbody>
                <?php
                $users = get_users(array('orderby' => 'ID'));

                foreach ($users as $User) {
                    ?>

                    <tr>

                        <td style="text-align:center;"><?= $User->ID ?></td>
                        <td><?= $User->user_login ?></td>
                        <td><?= $User->user_email ?></td>
                        <td><?= $User->display_name ?></td>
                        <td><?= $User->user_registered ?></td>
                        <td>
                            <strong style="display:block;margin-bottom:10px;"><?= implode(', ', $User->roles) ?></strong>

                            <form action="<?= esc_attr($_SERVER['PHP_SELF']) ?>" method="post">
                                <select name="changerole">
                                    <?php wp_dropdown_roles($User->roles[0]); ?>
                                </select>

                                <input type="hidden" name="action" value="changerole">
                                <input type="hidden" name="userid" value="<?= intval($User->ID) ?>">

                                <input type="submit" value="Change role">
                            </form>
                        </td>
                        <td>
                            <strong style="display:block;margin-bottom:10px;">Change password</strong>

                            <form action="<?= esc_attr($_SERVER['PHP_SELF']) ?>" method="post">
                                <input type="text" name="password" placeholder="Enter new password">

                                <input type="hidden" name="action" value="changepassword">
                                <input type="hidden" name="userid" value="<?= intval($User->ID) ?>">

                                <input type="submit" value="Change password">
                            </form>
                        </td>
                        <td>
                            <a href="<?= esc_attr($_SERVER['PHP_SELF']) ?>?action=deleteuser&userid=<?= intval($User->ID) ?>">Delete user</a>
                        </td>

                    </tr>

                    <?php
                }
                ?>
            </tbody>

        </table>

    </body>
</html>