<?php
session_start();
include('conf/config.php');
include('conf/checklogin.php');
check_login();
$admin_id = $_SESSION['admin_id'];

if (isset($_POST['deposit'])) {
    $tr_code = $_POST['tr_code'];
    $account_id = $_GET['account_id'];
    $acc_name = $_POST['acc_name'];
    $account_number = $_GET['account_number'];
    $acc_type = $_POST['acc_type'];
    $tr_type = $_POST['tr_type'];
    $tr_status = $_POST['tr_status'];
    $client_id = $_GET['client_id'];
    $client_name = $_POST['client_name'];
    $client_national_id = $_POST['client_national_id'];
    $transaction_amt = $_POST['transaction_amt'];
    $client_phone = $_POST['client_phone'];
    $notification_details = "$client_name Has Deposited $ $transaction_amt To Bank Account $account_number";
    $receiving_acc_no = ''; // or set to NULL if this field is optional
    $receiving_acc_name = '';
    $receiving_acc_holder = '';

    // Fetch the current account amount
    $stmt = $mysqli->prepare("SELECT acc_amount FROM iB_bankAccounts WHERE account_id = ?");
    $stmt->bind_param('i', $account_id);
    $stmt->execute();
    $stmt->bind_result($acc_amount);
    $stmt->fetch();
    $stmt->close();

    // Calculate the new account amount
    $new_acc_amount = $acc_amount + $transaction_amt;

    // Add this for better debugging
    error_log("Deposit request received with the following details:");
    error_log("tr_code: $tr_code");
    error_log("account_id: $account_id");
    error_log("acc_name: $acc_name");
    error_log("account_number: $account_number");
    error_log("acc_type: $acc_type");
    error_log("tr_type: $tr_type");
    error_log("tr_status: $tr_status");
    error_log("client_id: $client_id");
    error_log("client_name: $client_name");
    error_log("client_national_id: $client_national_id");
    error_log("transaction_amt: $transaction_amt");
    error_log("client_phone: $client_phone");
    error_log("receiving_acc_no: $receiving_acc_no");
    error_log("receiving_acc_name: $receiving_acc_name");
    error_log("receiving_acc_holder: $receiving_acc_holder");
    error_log("notification_details: $notification_details");

    $mysqli->begin_transaction();

    try {
        // Insert transaction
        $query = "INSERT INTO iB_Transactions (tr_code, account_id, acc_name, account_number, acc_type, tr_type, tr_status, client_id, client_name, client_national_id, transaction_amt, client_phone, acc_amount, receiving_acc_no, receiving_acc_name, receiving_acc_holder) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param('sisssssississsss', $tr_code, $account_id, $acc_name, $account_number, $acc_type, $tr_type, $tr_status, $client_id, $client_name, $client_national_id, $transaction_amt, $client_phone, $new_acc_amount, $receiving_acc_no, $receiving_acc_name, $receiving_acc_holder);
        if ($stmt->execute()) {
            // Update account balance
            $update_query = "UPDATE iB_bankAccounts SET acc_amount = ? WHERE account_id = ?";
            $update_stmt = $mysqli->prepare($update_query);
            if (!$update_stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $update_stmt->bind_param('di', $new_acc_amount, $account_id);
            if ($update_stmt->execute()) {
                // Insert notification
                $notification = "INSERT INTO iB_notifications (notification_details) VALUES (?)";
                $stmt = $mysqli->prepare($notification);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $mysqli->error);
                }
                $stmt->bind_param('s', $notification_details);
                if ($stmt->execute()) {
                    $mysqli->commit();
                    $success = "Money Deposited";
                    error_log($success);
                } else {
                    throw new Exception("Notification insertion failed: " . $stmt->error);
                }
            } else {
                throw new Exception("Account update failed: " . $update_stmt->error);
            }
        } else {
            throw new Exception("Transaction insertion failed: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $mysqli->rollback();
        $err = "Please Try Again Or Try Later: " . $e->getMessage();
        error_log($err);
    }
}
?>


<!DOCTYPE html>
<html>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<?php include("dist/_partials/head.php"); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <?php include("dist/_partials/nav.php"); ?>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?php include("dist/_partials/sidebar.php"); ?>

        <!-- Content Wrapper. Contains page content -->
        <?php
        $account_id = $_GET['account_id'];
        $ret = "SELECT * FROM  iB_bankAccounts WHERE account_id = ? ";
        $stmt = $mysqli->prepare($ret);
        $stmt->bind_param('i', $account_id);
        $stmt->execute(); //ok
        $res = $stmt->get_result();
        $cnt = 1;
        while ($row = $res->fetch_object()) {

        ?>
            <div class="content-wrapper">
                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1>Deposit Money</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="pages_dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="pages_deposits">iBank Finances</a></li>
                                    <li class="breadcrumb-item"><a href="pages_deposits">Deposits</a></li>
                                    <li class="breadcrumb-item active"><?php echo $row->acc_name; ?></li>
                                </ol>
                            </div>
                        </div>
                    </div><!-- /.container-fluid -->
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <!-- left column -->
                            <div class="col-md-12">
                                <!-- general form elements -->
                                <div class="card card-purple">
                                    <div class="card-header">
                                        <h3 class="card-title">Fill All Fields</h3>
                                    </div>
                                    <!-- form start -->
                                    <form method="post" enctype="multipart/form-data" role="form">
                                        <div class="card-body">

                                            <div class="row">
                                                <div class=" col-md-4 form-group">
                                                    <label for="exampleInputEmail1">Client Name</label>
                                                    <input type="text" readonly name="client_name" value="<?php echo $row->client_name; ?>" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                                <div class=" col-md-4 form-group">
                                                    <label for="exampleInputPassword1">Client National ID No.</label>
                                                    <input type="text" readonly value="<?php echo $row->client_national_id; ?>" name="client_national_id" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                                <div class=" col-md-4 form-group">
                                                    <label for="exampleInputEmail1">Client Phone Number</label>
                                                    <input type="text" readonly name="client_phone" value="<?php echo $row->client_phone; ?>" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class=" col-md-4 form-group">
                                                    <label for="exampleInputEmail1">Account Name</label>
                                                    <input type="text" readonly name="acc_name" value="<?php echo $row->acc_name; ?>" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                                <div class=" col-md-4 form-group">
                                                    <label for="exampleInputPassword1">Account Number</label>
                                                    <input type="text" readonly value="<?php echo $row->account_number; ?>" name="account_number" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                                <div class=" col-md-4 form-group">
                                                    <label for="exampleInputEmail1">Account Type | Category</label>
                                                    <input type="text" readonly name="acc_type" value="<?php echo $row->acc_type; ?>" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class=" col-md-6 form-group">
                                                    <label for="exampleInputEmail1">Transaction Code</label>
                                                    <?php
                                                    //PHP function to generate random account number
                                                    $length = 20;
                                                    $_transcode =  substr(str_shuffle('0123456789QWERgfdsazxcvbnTYUIOqwertyuioplkjhmPASDFGHJKLMNBVCXZ'), 1, $length);
                                                    ?>
                                                    <input type="text" name="tr_code" readonly value="<?php echo $_transcode; ?>" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                                <div class=" col-md-6 form-group">
                                                    <label for="exampleInputPassword1">Amount Deposited(GHS)</label>
                                                    <input type="text" name="transaction_amt" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                                <div class=" col-md-4 form-group" style="display:none">
                                                    <label for="exampleInputPassword1">Transaction Type</label>
                                                    <input type="text" name="tr_type" value="Deposit" required class="form-control" id="exampleInputEmail1">
                                                </div>
                                                <div class=" col-md-4 form-group" style="display:none">
                                                    <label for="exampleInputPassword1">Transaction Status</label>
                                                    <input type="text" name="tr_status" value="Success " required class="form-control" id="exampleInputEmail1">
                                                </div>

                                            </div>

                                        </div>
                                        <!-- /.card-body -->
                                        <div class="card-footer">
                                            <button type="submit" name="deposit" class="btn btn-success">Deposit Funds</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- /.card -->
                            </div><!-- /.container-fluid -->
                </section>
                <!-- /.content -->
            </div>
        <?php } ?>
        <!-- /.content-wrapper -->
        <?php include("dist/_partials/footer.php"); ?>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- bs-custom-file-input -->
    <script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.min.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="dist/js/demo.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            bsCustomFileInput.init();
        });
    </script>
</body>

</html>