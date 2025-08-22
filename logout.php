<?php
session_start(); // Začne sejo (če že ni aktivna)
session_unset(); // Odstrani vse podatke seje
session_destroy(); // Uniči sejo

// Preusmeri uporabnika na prijavno stran
header("Location: index.html"); 
exit();
?>