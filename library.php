<?php # A function which outputs the error message when all flights have been fully booked 
function noAvailableFlights() {
    echo <<< _END
<title>MyFlights</title>
<head><b>MyFlights</b></head>

<p>We are sorry, but there are currently no available flights to any destination
we support:<p>

    <p>No Flights</p>

Please try again later.
_END;
}?>

<?php # A function to create the form for email submission which confirms a booking 
function confirm() {
         printf("Enter your email address below to confirm booking");
         echo'<form action = "" method="post">
         E-Mail: <input type = "text" name = "email" required>
         <input type = "submit" name = "submit" value = "Submit">
         </form>';
}?>
<?php #A function to validate an email
function validateEmail($msg){
    if(preg_match("/[\w\.\-]+\@([\w]+\.)+/",$msg)){
        return true;
    }
}?>