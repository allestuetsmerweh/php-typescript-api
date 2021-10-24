<?php

echo <<<'ZZZZZZZZZZ'
<!DOCTYPE html>
<html lang='de'>
<head>
    <title>PHP TypeScript API Example</title>
    <script src='dist/example.js'></script>
</head>
<body>
    <h2>Divide</h2>
    <form onsubmit='return example.submitDivideForm(this)'>
        <input type='text' name='dividend' />
        <input type='text' name='divisor' />
        <input type='submit' value='Calculate' />
        <input type='text' name='result' readonly />
    </form>
</body>
</html>
ZZZZZZZZZZ;
