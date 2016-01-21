<!doctype html>
<?php
$HTPASSWD_FP = "./users.htpasswd";
require_once(dirname(__FILE__).'/Htpasswd.php');
$messages = array();
$errors = array();

if(!file_exists($HTPASSWD_FP)){
    $fh = @fopen($HTPASSWD_FP, 'w');
    if($fh){
        $messages[] = "Файл паролей успешно создан";
        fclose($fh);
    }
    else{
        $errors[] = "Ошибка создания файла с паролями";
    }
}

// Load existing users
$users = array();
$fh = @fopen($HTPASSWD_FP, "r");
if ($fh) {
    while (($line = fgets($fh)) !== false) {
        $users[] = explode(":", $line)[0];
    }
    fclose($fh);
}

// Handle requests
if(array_key_exists('action', $_REQUEST)){
    if($_REQUEST['action'] == "add-user"){

        $username = trim($_REQUEST['username']);
        $password = trim($_REQUEST['passwd']);
        if($username && $password){
            if(!in_array($username, $users)){
                try {
                    $htpasswd = new Htpasswd($HTPASSWD_FP);
                    $htpasswd->addUser($username, $password, Htpasswd::ENCTYPE_MD5);
                    $messages[] = "Новый пользователь успешно добавлен";
                    $users[] = $username;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            else{
                $errors[] = "Пользователь $username уже существует";
            }
        }
        else{
            $errors[] = "Имя пользователя или пароль не указаны";
        }
    }
    if($_REQUEST['action'] == "delete"){
        $username = trim($_REQUEST['username']);
        if($username){
            if(($key = array_search($username, $users)) !== false) {
                try {
                    $htpasswd = new Htpasswd($HTPASSWD_FP);
                    $htpasswd->deleteUser($username);
                    $messages[] = "Пользователь $username успешно удален";
                    unset($users[$key]);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            else{
                $errors[] = "Пользователь $username не найден";
            }
        }

    }

}

?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Icecast менеджер паролей</title>
        <meta name="description" content="Icecast менеджер паролей">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
        <link rel="stylesheet" type="text/css" href="./css/sweetalert.css">
        <style>
         body {
             padding: 20px;
         }
        </style>
    </head>
    <body>

        <div class="row">
            <div class="col-md-12">
                <h3>Icecast менеджер паролей</h3>
                <?php
                foreach ($messages as $msg){
                    print "<p class=\"alert alert-success\">$msg</p>";
                }
                foreach ($errors as $error_msg){
                    print "<p class=\"alert alert-danger\">$error_msg</p>";
                }
                ?>
            </div>
        </div>

        <div class="row">

            <div class="col-md-5">
                <table class="table table-hover">
                    <tr>
                        <th>
                            Логин
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                    <?php
                    if(count($users)){
                        foreach ($users as $u){
                            print "<tr><td>$u</td><td align=\"right\"><button class=\"delete_user btn btn-warning\" data-username=\"$u\">Удалить</button></td></tr>";
                        }
                    }
                    else{
                         print "<tr><td colspan=\"2\">Список пользователей пуст</td></tr>";
                    }
                    ?>
                </table>

            </div>
            <div class="col-md-4">

                <form class="form-horizontal" method="post" id="new-user-form">
                    <fieldset>

                        <!-- Form Name -->
                        <legend>Добавить нового пользователя</legend>

                        <!-- Text input-->
                        <div class="form-group" id="group-newusername">
                            <label class="col-md-4 control-label" for="new-username">Логин</label>
                            <div class="col-md-4">
                                <input id="new-username" name="username" type="text" placeholder="" class="form-control input-md" required="">
                                <span class="help-block">Латинскими буквами</span>
                            </div>
                        </div>


                        <!-- Text input-->
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="passwd">Пароль</label>
                            <div class="col-md-4">
                                <input id="passwd" name="passwd" type="text" placeholder="" class="form-control input-md" required="">
                            </div>
                        </div>

                        <!-- Button -->
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="singlebutton"></label>
                            <div class="col-md-4">
                                <button id="singlebutton" name="singlebutton" class="btn btn-primary">Добавить пользователя</button>
                            </div>
                        </div>

                    </fieldset>
                    <input type="hidden" name="action" value="add-user">
                </form>

            </div>
        </div>
      <script src="./js/sweetalert.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
      <script>
         $( document ).ready(function() {
             $('.delete_user').on('click', function(e){
                 e.preventDefault();
                 var username = $(e.target).data('username');
                 if(username){
                     swal(
                         {
                             title: "Вы уверены?",
                                 text: "Пользователь " + username + " будет удален",
                                 type: "warning",
                                 showCancelButton: true,
                                 confirmButtonColor: "#DD6B55",
                                 confirmButtonText: "Да!",
                                 closeOnConfirm: true
                                 }, function(){
                                     window.location = window.location.pathname + "?action=delete&username=" + username;
                                 });
                 }
             })
             $('#new-user-form').submit(function(e){
                 var existingUsers = [],
                     newUsername = $('#new-username').val();

                 $.each($('.delete_user'), function(i, elm){
                     existingUsers.push($(elm).data('username'));
                 });

                 if(existingUsers.indexOf(newUsername) >= 0){
                     $('#help-newusername').remove();
                     $('#group-newusername').addClass('has-error');
                     $('#new-username').select();
                     $('#new-username').focus();
                     $('#new-username').after('<span id="help-newusername" class="help-block">Пользователь уже существует</span>');
                     e.preventDefault();
                 }
                 else if(!/[a-zA-Z0-9_]/.test(newUsername)){
                     $('#new-username').select();
                     $('#new-username').focus();
                     $('#group-newusername').addClass('has-error');
                     e.preventDefault();
                 }
             })

         });
function checkForm(){
    return false;
}
      </script>
    </body>
</html>
