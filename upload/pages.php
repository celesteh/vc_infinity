<?php
// Initialize the session
session_start();
include_once "config.php";
include_once "functions.php";
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}


if (! lazy_power_check($_SESSION["id"], $pdo, 60)){
    header("location: index.php");
}

if(ISSET($_POST['upload'])){


    if(isset($_POST["page_scorecode"])){
        $score = trim($_POST["page_scorecode"]);
        //echo $score;
    }
 

    if(isset($_POST["page_num"])){
        $page = trim($_POST["page_num"]);

        $sql = "SELECT page_id FROM score_pages WHERE page_num = :page_num AND page_scorecode = :page_scorecode";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":page_num", $param_num, PDO::PARAM_INT);
            $stmt->bindParam(":page_scorecode", $score, PDO::PARAM_STR);
            // Set parameters
            $param_num = (int)$page;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                if($stmt->rowCount() >= 1){
                   $page_err = _("This page has already been uploaded.");
                } 
            } else{
               
                $page_err = _("Oops! Something went wrong. Please try again later.");
            }

            // Close statement
            unset($stmt);
        }

    }

    if(empty($page_err)){

    $image_name = $_FILES['image']['name'];
    $image_temp = $_FILES['image']['tmp_name'];
    $image_size = $_FILES['image']['size'];
    $ext = explode(".", $image_name);
    $end = end($ext);
    $allowed_ext = array("jpg", "jpeg", "gif", "png");
    $name = $score . "_". $page . ".".$end;
    $path = "../score_pages/".$name;
    if(in_array($end, $allowed_ext)){
        if($image_size > 5242880){
            echo "<script>alert('File too large!')</script>";
            echo "<script>window.location = 'index.php'</script>";
        }else{
            if(move_uploaded_file($image_temp, $path)){

                $size = getimagesize($path);

                //mysqli_query($conn, "INSERT INTO `image` VALUES('', '$name', '$path')") or die(mysqli_error());
                $sql =  "INSERT INTO score_pages (page_img_file, page_num, page_scorecode, page_x, page_y) VALUES (:page_filename, :page_num, :page_scorecode, :width, :height)";

                if($stmt = $pdo->prepare($sql)){
                    // Bind variables to the prepared statement as parameters
                    $stmt->bindParam(":page_num", $param_num, PDO::PARAM_INT);
                    $stmt->bindParam(":page_scorecode", $score, PDO::PARAM_STR);
                    $stmt->bindParam(":page_filename", $name, PDO::PARAM_STR);
                    $stmt->bindParam(":width", $param_width, PDO::PARAM_INT);
                    $stmt->bindParam(":height", $param_height, PDO::PARAM_INT);
                    // Set parameters
                    $param_num = (int)$page;
                    $param_width = (int) $size[0];
                    $param_height = (int) $size[1];
        
                    // Attempt to execute the prepared statement
                    if($stmt->execute()){
        

                //echo "<script>alert('Image uploaded!')</script>";
                //echo "<script>window.location = 'index.php'</script>";
            }
        }
    }
}
    }else{

        $img_err = _("Invalid image format!") ." " . $end;

        echo '<script>alert("' . $img_err . '")</script>';
        //echo "<script>window.location = 'index.php'</script>";

}
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

    <style type="text/css">
        body{ font: 14px sans-serif; width:90%; padding: 20px;}
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Upload score pages</h1>
    </div>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
			<div class="form-inline">
                <div class="form-group <?php echo (!empty($img_err)) ? 'has-error' : ''; ?>">
                    <div class="row">
                        <div class="col-50l">  
				            <label>Upload here</label>
                        </div>
                        <div class="col-50r">
                            <input type="file" name="image" class="form-control" required="required"/>
                        </div>
                    </div>
                    <span class="help-block"><?php echo $img_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($page_err)) ? 'has-error' : ''; ?>">
                    <div class="row">
                        <div class="col-50l">                    
                            <label>Page number</label>
                        </div>
                        <div class="col-50r">
                            <input type="number" name="page_num" id="page_num" min="0" required="required">
                        </div>
                    </div>
                    <span class="help-block"><?php echo $page_err; ?></span>
                </div>

                <div class="row">
                    <div class="col-50l">                    
                        <label>Score</label>
                    </div>
                    <div class="col-50r">
                        <select name="page_scorecode" id="page_scorecode">
                            <?php        
            // get all scores
            $arsql = "SELECT s_scorecode, s_title FROM `scores` WHERE 1";
            if($arstmt = $pdo->prepare($arsql)){
                if($arstmt->execute()){
                    while($row = $arstmt->fetch()){
                        echo '<option value="' . $row["s_scorecode"] . '">' . $row["s_title"] . '</option>'; 
                    }
                }
            }
                            ?>
                        </select>

                    </div>
                </div>

                            

				<button class="btn btn-primary" name="upload"><span class="glyphicon glyphicon-upload"></span> Upload</button>
			</div>
		</form>
		<br />
		<div class="alert alert-info"><h3>Already Uploaded Pages</h3></div>
		<?php


                $sql = "SELECT page_img_file FROM `score_pages` WHERE 1 ";
                if($stmt = $pdo->prepare($sql)){
                    if($stmt->execute()){
                         while($fetch = $stmt->fetch()){
    

		?>
			<div style="border:1px solid #000; height:190px; width:190px; padding:4px; float:left; margin:10px;">
				<a href="../score_pages/<?php echo $fetch['page_img_file']?>"><img src="../score_pages/<?php echo $fetch['page_img_file']?>" width="180" height="180"/></a>
			</div>
		<?php
            }
        }
    }
		?><a class="btn btn-link" href="index.php">Go Home</a>
	</div>
</body>	
</html>