<?php 
/**
 * Starts session for user and checks if any flights are available i.e. selects from the database 
 * any locations with airlines with available spaces 
 */
session_start();
require_once "library.php";
require_once "login.php";

try {
    /** The idea to use count(*) on the select query came from user Marc B 
     * (https://stackoverflow.com\users\118068\marc-b)
     * (https://stackoverflow.com/questions/13478206/checking-for-an-empty-result-php-pdo-and-mysql)
     * accessed 28 July 2023
     */
    $pdo = new PDO($dsn,$db_username,$db_password,$opt);
    $result = $pdo->query("SELECT count(*) FROM flightsDB WHERE capacity > 0");
    $rows = $result->fetchColumn();

    if ($rows < 1) {
        die(noAvailableFlights());
    }
    } catch (PDOException $e) {
        exit("<p><b>Could Not Connect to Database</b></p>");
    }
    
?>

<!DOCTYPE html>
<html lang = "en">
<title>MyFlights</title>
<head>MyFlights Home</head>
<body>
<?php 
/**
 * Function to output the first dropdown menu containing the available destinations
 * This function first finds what destinations that have an airline with a capacity above zero 
 * It then selects distinct destinations 
 * The select form is populated by accessing the array created by the PDO in a foreach loop  
 */
function getDestination(){
    require "login.php";
try{
    $pdo = new PDO($dsn,$db_username,$db_password,$opt);
    $res = $pdo->query("SELECT DISTINCT destination FROM flightsDB WHERE capacity > 0");
    
     echo'
        <form name = "d1" method = "get">
           <label for = "d1"> Select Destination</label>
           <select name = "destination" onChange="document.d1.submit()">
           <option value = "">Select</option> ';
                foreach ($res as $row){
                   echo"<option>",$row['destination'],"</option>";
                }
           echo '</Select>
           <input type = "hidden" name = "submitted" value ="yes">
        </form> ';


            $_SESSION['destination'] = $_GET['destination'];

        
           
            
    } catch (PDOException $e){
        $pdo->rollback();    
        exit("PDO Error: \n".$e->getMessage());
        echo "\n All seats have been booked";
   }

}?>

<?php
/** 
* A function to populate a second form with airlines with available seats to 
* the user's chosen destination. It also displays the price for the flight.
* The first select query selects eligible airlines and creates a drop down menu 
* An onChange event triggers the second query which selects capacity of the chosen airline
* and calculates the price which is stored in a session variable called price
* @Param $d - Destination passed to the function this makes the function work as session 
* variable can't be used in a select query
*/
 function getAirline($d){
    include "login.php";

    try{
        $PDO = new PDO($dsn,$db_username,$db_password,$opt);
        $sql = "SELECT airline FROM flightsDB WHERE capacity > 0 and destination = :destination";
        $res2 = $PDO->prepare($sql);
        $res2->bindValue(':destination',$_SESSION['destination']);
        $res2 -> execute();
        echo '<form name = "a1" method = "POST">
              <label for = "a1"> Select Airline</label>
                <select name = "airline" onChange="document.a1.submit()">
                <option value = "">Select</option>'; 
        foreach($res2 as $row){
        echo "<option>",$row['airline'],"</option>";
        }
        echo'<input type = "hidden" name = "air" value = "yes">';    
        echo'</form>';  
        foreach( $_REQUEST as $key){
        
        $result = $PDO->query("SELECT * from flightsDB WHERE airline = '$key' and destination = '$d'");
        $result -> bindColumn(3,$c);
        $result -> bindColumn(4,$bp);
        $result->fetch(PDO::FETCH_BOUND);
        global $price; 
        $price = $bp - ($c * 200);
        $_SESSION['price'] = $price;
        }
        $_SESSION['airline'] = $_REQUEST['airline'];
        printf("This flight to ".$d." with ".$_REQUEST['airline']." will cost £".$price ."<br>");
    }catch(PDOException $e){
        exit("PDO :".$e->getMessage());
    }
}
    ?>
<?php  
/**
 * This is where the main processing of the system happens
 * It first checks if a destination has been selected and
 * outputs a message accordingly.
 *    
 **/
if(!isset($_SESSION['destination'])){
    print("<p><b>no destination selected</b></p>\n");
}
/**
 * The system checks if a submit has been sent signalling an email
 * has been entered marking a complete booking. 
 * If no submit has been posted the function getDestination is called to load
 * the first dropdown. After this the system checks for a hidden input
 * which confirms if a destination has been chosen and if that destination
 * has any available flights then calls getAirline or returns a message if
 * not and calls getDestination again
 */
if(!isset($_POST['submit'])){
   getDestination();
   printf("You chose ".$_SESSION['destination']."<br>");
   if($_GET['submitted'] == 'yes'){
    printf("Checking Availability <br>");
    try {
        $PDO2 = new PDO($dsn,$db_username,$db_password,$opt);
        $SQL = "SELECT count(*) FROM flightsDB WHERE capacity > 0 and destination = :destination";
        $check = $PDO2 -> prepare($SQL);
        $dest = $_SESSION['destination'];
        $check -> bindValue(':destination',$dest);
        $check -> execute();
        $rows = $check->fetchColumn();
    
        if ($rows < 1) {
            echo "That destination has been fully booked please choose another";
            getDestination();
        }else{
            getAirline($_SESSION['destination']); 
            confirm(); 
        }
        } catch (PDOException $e) {
            exit("<p><b>Could Not Connect to Database</b></p>");
        }
    
   }
}
/**
 * If a post has been submit, the system validates the email address and returns 
 * a message if it is invalid.
 * Otherwise the system inserts the booking into the bookings table in the DB
 * and uses and update query to change the number of remaining seats on the 
 * chosen airline.
 * If the number of seats would be reduced to below 0 the DB throws an exception
 * which is caught and returns an error to the user that the flight has been 
 * fully booked
 */
else{
    include "login.php";
   $error = validateEmail($_POST['email']);
    if ($error == 1){    
    try{
     
    $PDO2 = new PDO($dsn,$db_username,$db_password,$opt);
    $PDO2->beginTransaction();
    $SQL = ("INSERT into bookings VALUES(:email,:destination,:airline,:cost)");
    $store = $PDO2->prepare($SQL);
    $store -> bindParam(':email',$email);
    $store -> bindParam(':destination',$dest);
    $store -> bindParam(':airline',$key);
    $store -> bindParam(':cost',$price);

    $email = $_POST['email'];
    $dest = $_SESSION['destination'];
    $key = $_SESSION['airline'];
    $price = $_SESSION['price'];
    $store -> execute();

    $SQL = ("UPDATE flightsDB SET capacity = capacity -1  WHERE destination  = :destination and airline = :airline");
    $update = $PDO2 -> prepare($SQL);
    $update -> bindValue(':destination',$dest);
    $update -> bindValue(':airline',$key);
    $update -> execute();
    $PDO2 -> commit();
    }catch(PDOException $e){
        $PDO2 -> rollback();
        exit("Booking Unsuccessful <br> All seats on this Airline have been booked."); 
}  
printf("<br> Booking Succesful");
    }
    else{
        printf("<br> Booking Unsucessful <br> Invalid E-Mail");
    }
    
}

?>
    <?php 
        ini_set('display_errors ',1);
        error_reporting(E_ALL | E_STRICT );
    ?>
 </body>
</html>