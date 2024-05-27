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
        <hr />
        <h2>Square root</h2>
        <form onsubmit='return example.submitSqrtForm(this)'>
            <input type='text' name='input' />
            <input type='submit' value='Calculate' />
            <input type='text' name='result' readonly />
        </form>
        <hr />
        <h2>Swiss public transport connection</h2>
        <form onsubmit='return example.submitSPTransportConnectionForm(this)'>
            <div>From: <input type='text' name='from' /></div>
            <div>To: <input type='text' name='to' /></div>
            <div>Via: <input type='text' name='via' /> (comma-separated)</div>
            <div>Date: <input type='text' name='date' /></div>
            <div>Time: <input type='text' name='time' /></div>
            <div>
                <input type='radio' name='isArrivalTime' value='0' /> Departure
                &nbsp;
                <input type='radio' name='isArrivalTime' value='1' /> Arrival
            </div>
            <div><input type='submit' value='Calculate' /></div>
            <input type='text' name='result' readonly />
        </form>
    </body>
    </html>
    ZZZZZZZZZZ;
