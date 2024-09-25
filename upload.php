<?php

declare(strict_types=1);

class ConvertCSV
{
    private string $filename;
    private array $json = [];
    private array $headings;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function convertToJSON()
    {
        //READ FILE AS STRING
        $content = file_get_contents($this->filename);
        //REPLACE OLD STYLE LINE ENDINGS
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);

        //SPLIT STRING INTO ARRAY
        $lines = explode("\n", $content);

        //EXTRACT HEADERS AND PARSE TO ARRAY
        $this->headings = str_getcsv(array_shift($lines));

        //LOOP OVER EACH LINE
        foreach ($lines as $index => $line) {
            if (!empty($line)) {
                //PARSE ROW TO ARRAY
                $row = str_getcsv($line);
                
                if (count($row) == count($this->headings)) {
                    //MAP COLUMN HEADINGS AS KEYS FOR EACH ROW
                    $row = array_combine($this->headings, $row);
                    $row = array_change_key_case($row, CASE_LOWER);
                    $this->json[$index] = $row;
                }
            }
        }

        return json_encode(array_values($this->json), JSON_PRETTY_PRINT);
    }

    public function saveJSON(string $outputFilename)
    {
        //STORE CONVERTED JSON AS VARIABLE AND WRITE AS A FILE
        $convertedJSON = $this->convertToJSON();
        file_put_contents($outputFilename, $convertedJSON);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csvfile']) && $_FILES['csvfile']['error'] === UPLOAD_ERR_OK) {
        // Get details of the uploaded file
        $fileTmpPath = $_FILES['csvfile']['tmp_name'];
        $fileName = $_FILES['csvfile']['name'];
        $fileSize = $_FILES['csvfile']['size'];
        $fileType = $_FILES['csvfile']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $maxSize = 2 * 1024 * 1024; // 2MB in bytes

        // Check if the file has the correct extension
        if ($fileExtension === 'csv' && $fileSize <= $maxSize) {
            // Directory where the file will be saved
            $uploadFileDir = './uploaded_files/';
            $dest_path = $uploadFileDir . $fileName;

            // Move the file to the desired directory
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $convert = new ConvertCSV($dest_path);
                $jsonResult = $convert->convertToJSON();

                // Display the JSON result
                header('Content-Type: application/json');
                echo $jsonResult;

                // Optionally save the JSON to a file
                $convert->saveJSON($uploadFileDir . 'convertedjson.json');
            } else {
                echo 'There was some error moving the file to upload directory.';
            }
        } else {
            echo 'Upload failed. Allowed file types: .csv';
        }
    } else {
        echo 'There was some error with the file upload.';
    }
} else {
    echo 'Invalid request method.';
}