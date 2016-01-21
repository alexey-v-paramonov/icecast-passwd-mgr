<!doctype html>
<?php
$HTPASSWD_FP = "./users.htpasswd";
require_once(dirname(__FILE__).'/Htpasswd.php');
$messages = array();
$errors = array();

if(!file_exists($HTPASSWD_FP)){
    $fh = @fopen($HTPASSWD_FP, 'w');
    if($fh){
        $messages[] = "Password file has been created successfully";
        fclose($fh);
    }
    else{
        $errors[] = "Unable to create the password file";
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
                    $messages[] = "User has been added successfully";
                    $users[] = $username;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            else{
                $errors[] = "User $username already exists";
            }
        }
        else{
            $errors[] = "Username or password not set";
        }
    }
    if($_REQUEST['action'] == "delete"){
        $username = trim($_REQUEST['username']);
        if($username){
            if(($key = array_search($username, $users)) !== false) {
                try {
                    $htpasswd = new Htpasswd($HTPASSWD_FP);
                    $htpasswd->deleteUser($username);
                    $messages[] = "User $username has been removed successfully";
                    unset($users[$key]);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            else{
                $errors[] = "User $username not found";
            }
        }

    }

}

?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Icecast password manager</title>
        <meta name="description" content="Icecast password manager">
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
                <h3>Icecast password manager</h3>
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
                            Username
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                    <?php
                    if(count($users)){
                        foreach ($users as $u){
                            print "<tr><td>$u</td><td align=\"right\"><button class=\"delete_user btn btn-warning\" data-username=\"$u\">Delete</button></td></tr>";
                        }
                    }
                    else{
                         print "<tr><td colspan=\"2\">User list is empty</td></tr>";
                    }
                    ?>
                </table>

            </div>
            <div class="col-md-4">

                <form class="form-horizontal" method="post" id="new-user-form">
                    <fieldset>

                        <!-- Form Name -->
                        <legend>Add new user</legend>

                        <!-- Text input-->
                        <div class="form-group" id="group-newusername">
                            <label class="col-md-4 control-label" for="new-username">Username</label>
                            <div class="col-md-4">
                                <input id="new-username" name="username" type="text" placeholder="" class="form-control input-md" required="">
                                <span class="help-block">Latin characters only</span>
                            </div>
                        </div>


                        <!-- Text input-->
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="passwd">Password</label>
                            <div class="col-md-4">
                                <input id="passwd" name="passwd" type="text" placeholder="" class="form-control input-md" required="">
                            </div>
                        </div>

                        <!-- Button -->
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="singlebutton"></label>
                            <div class="col-md-4">
                                <button id="singlebutton" name="singlebutton" class="btn btn-primary">Add user</button>
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
                             title: "Are you sure?",
                                 text: "User " + username + " will be removed",
                                 type: "warning",
                                 showCancelButton: true,
                                 confirmButtonColor: "#DD6B55",
                                 confirmButtonText: "Yes!",
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
                     $('#new-username').after('<span id="help-newusername" class="help-block">Username exists</span>');
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
