  <?php
        $CounterFile = "hitcount.txt";
        
        if(file_exists($CounterFile)){
            $Hits = file_get_contents($CounterFile);
            ++$Hits;
        }
        else{
            $Hits = 500;
        }
             
        file_put_contents($CounterFile, $Hits);
        ?>
