#!/bin/bash  

for i in {0..15}
do
    php ChocoMain.php 16 $i 3000 4100 ColorBySector debug&
done
