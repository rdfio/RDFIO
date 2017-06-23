#!/bin/bash
echo 'drop database circle_test;' | mysql -u root;
echo 'create database circle_test;' | mysql -u root;
echo 'grant all on circle_test.* on ubuntu@localhost;' | mysql -u root;
