<?php
session_start();
include("../conexion.php");

$id = $_GET['id'];

$conn->query("DELETE FROM administradores WHERE id=$id");

header("Location: panel.php");
