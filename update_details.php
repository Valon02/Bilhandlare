<?php

set_time_limit(0);

require "db.php";

$conn->set_charset("utf8mb4");


// Visa resultat direkt medan scriptet körs
while (ob_get_level() > 0) {
    ob_end_flush();
}

ob_implicit_flush(true);

echo "<h2>Uppdaterar fordonsdetaljer</h2>";
echo "Startar...<br><br>";

flush();


// Hämta bilar som ännu inte har detaljkontrollerats
$result = $conn->query("
    SELECT vehicle_id, url
    FROM cars
    WHERE details_scraped = 0
    ORDER BY id ASC
");


if (!$result) {

    die(
        "Kunde inte hämta bilar från databasen: "
        . $conn->error
    );
}


$totalCars =
    $result->num_rows;


echo "Antal bilar att kontrollera: "
    . $totalCars
    . "<br><br>";

flush();


// Förbered UPDATE-frågan
$updateStmt = $conn->prepare("
    UPDATE cars
    SET registration_number = ?,
        description = ?,
        details_scraped = 1
    WHERE vehicle_id = ?
");


if (!$updateStmt) {

    die(
        "Kunde inte förbereda UPDATE-frågan: "
        . $conn->error
    );
}


// Räknare
$processed = 0;
$updated = 0;
$failed = 0;
$missingReg = 0;
$withDescription = 0;
$withoutDescription = 0;


// Gå igenom alla bilar
while ($car = $result->fetch_assoc()) {

    $vehicleId =
        (int) $car["vehicle_id"];

    $carUrl =
        $car["url"];

    $processed++;


    // Kontrollera att bilen har en URL
    if (empty($carUrl)) {

        echo "Bil "
            . $processed
            . " / "
            . $totalCars
            . " - ID "
            . $vehicleId
            . " - saknar URL.<br>";

        flush();

        $failed++;

        continue;
    }


    // Hämta detaljsidan med cURL
    $ch =
        curl_init();


    curl_setopt(
        $ch,
        CURLOPT_URL,
        $carUrl
    );

    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_FOLLOWLOCATION,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        20
    );

    curl_setopt(
        $ch,
        CURLOPT_CONNECTTIMEOUT,
        10
    );

    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    );


    $html =
        curl_exec($ch);


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    // cURL-fel
    if ($html === false) {

        echo "Bil "
            . $processed
            . " / "
            . $totalCars
            . " - ID "
            . $vehicleId
            . " - cURL-fel: "
            . htmlspecialchars(
                curl_error($ch)
            )
            . "<br>";

        flush();

        curl_close($ch);

        $failed++;

        usleep(700000);

        continue;
    }


    curl_close($ch);


    // Kontrollera HTTP-status
    if ($httpCode !== 200) {

        echo "Bil "
            . $processed
            . " / "
            . $totalCars
            . " - ID "
            . $vehicleId
            . " - HTTP-status "
            . $httpCode
            . "<br>";

        flush();

        $failed++;

        usleep(700000);

        continue;
    }


    // Läs HTML
    $dom =
        new DOMDocument();


    libxml_use_internal_errors(true);


    $loaded =
        $dom->loadHTML($html);


    libxml_clear_errors();


    if (!$loaded) {

        echo "Bil "
            . $processed
            . " / "
            . $totalCars
            . " - ID "
            . $vehicleId
            . " - kunde inte läsa HTML.<br>";

        flush();

        $failed++;

        usleep(700000);

        continue;
    }


    $detailXpath =
        new DOMXPath($dom);


    // Registreringsnummer
    $registrationNumber = "";


    $regLabel =
        $detailXpath->query(
            '//p[normalize-space(.)="Reg.nr"]'
        )->item(0);


    if ($regLabel) {

        $parent =
            $regLabel->parentNode;


        $regValue =
            $detailXpath->query(
                './/p[2]',
                $parent
            )->item(0);


        if ($regValue) {

            $registrationNumber =
                trim(
                    $regValue->textContent
                );
        }
    }


    // Ta bort mellanslag från registreringsnumret
    $registrationNumber =
        preg_replace(
            '/\s+/',
            '',
            $registrationNumber
        );


    if ($registrationNumber === "") {
        $missingReg++;
    }


    // Beskrivning
    $description = "";


    $descriptionButton =
        $detailXpath->query(
            '//button[normalize-space(.)="Beskrivning"]'
        )->item(0);


    if ($descriptionButton) {

        $descriptionPanel =
            $detailXpath->query(
                './following-sibling::div[1]',
                $descriptionButton
            )->item(0);


        if ($descriptionPanel) {

            $descriptionNode =
                $detailXpath->query(
                    './/div[
                        contains(@class, "text-base")
                        and contains(@class, "leading-relaxed")
                    ]',
                    $descriptionPanel
                )->item(0);


            if ($descriptionNode) {

                $description =
                    trim(
                        $descriptionNode->textContent
                    );


                // Gör flera mellanslag och radbrytningar till ett mellanslag
                $description =
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $description
                    );
            }
        }
    }


    // Räkna annonser med och utan beskrivning
    if ($description !== "") {

        $withDescription++;

    } else {

        $withoutDescription++;
    }


    // Uppdatera bilen i databasen
    $updateStmt->bind_param(
        "ssi",
        $registrationNumber,
        $description,
        $vehicleId
    );


    if ($updateStmt->execute()) {

        $updated++;

    } else {

        echo "Bil "
            . $processed
            . " / "
            . $totalCars
            . " - ID "
            . $vehicleId
            . " - Databasfel: "
            . htmlspecialchars(
                $updateStmt->error
            )
            . "<br>";

        flush();

        $failed++;

        usleep(700000);

        continue;
    }


    // Visa progress
    echo "Bil "
        . $processed
        . " / "
        . $totalCars
        . " - ID "
        . $vehicleId
        . " - Reg.nr: "
        . (
            $registrationNumber !== ""
                ? htmlspecialchars(
                    $registrationNumber
                )
                : "saknas"
        )
        . " - Beskrivning: "
        . (
            $description !== ""
                ? "ja"
                : "nej"
        )
        . "<br>";


    flush();


    // Kort paus mellan annonserna
    usleep(700000);
}


// Stäng databasfrågan och anslutningen
$updateStmt->close();

$conn->close();


// Slutresultat
echo "<hr>";

echo "<h2>Klar!</h2>";

echo "Kontrollerade: "
    . $processed
    . "<br>";

echo "Uppdaterade: "
    . $updated
    . "<br>";

echo "Misslyckade: "
    . $failed
    . "<br>";

echo "Saknade registreringsnummer: "
    . $missingReg
    . "<br>";

echo "Med beskrivning: "
    . $withDescription
    . "<br>";

echo "Utan beskrivning: "
    . $withoutDescription
    . "<br>";

?>