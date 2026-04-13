<?php
$pdo = new PDO(
  "mysql:host=hverdagsnorskn03.mysql.domeneshop.no;dbname=hverdagsnorskn03;charset=utf8mb4",
  "hverdagsnorskn03",
  "mKUpt4Bv1!guxe672",
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]
);

/* ==========================================================
   LEX / FLASH (hverdagsnorskn01)
========================================================== */

db('lex') = new PDO(
  "mysql:host=hverdagsnorskn01.mysql.domeneshop.no;
   dbname=hverdagsnorskn01;
   charset=utf8mb4",
  "hverdagsnorskn01",
  "mKUpt4Bv1!mKUpt4Bv1!",
  [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
  ]
);