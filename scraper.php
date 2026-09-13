<?php

set_time_limit(0);

require "db.php";

$conn->set_charset("utf8mb4");


// Inställningar
$targetCars = 1100;
$maxPages = 60;

$baseUrl = "https://bilweb.se/sok?page=";


// Visa resultat direkt medan scrapern körs
while (ob_get_level() > 0) {
    ob_end_flush();
}

ob_implicit_flush(true);


echo "<h2>Bilweb scraper</h2>";

echo "Mål: "
    . $targetCars
    . " annonser<br><br>";

flush();


// Förbered INSERT
// INSERT IGNORE gör att samma vehicle_id inte sparas igen
$insertStmt = $conn->prepare("
    INSERT IGNORE INTO cars
    (
        vehicle_id,
        brand,
        model,
        model_year,
        price,
        mileage,
        fuel_type,
        gearbox,
        url,
        details_scraped
    )
    VALUES
    (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, 0
    )
");


if (!$insertStmt) {

    die(
        "Kunde inte förbereda INSERT-frågan: "
        . $conn->error
    );
}


// Räknare
$processedCars = 0;
$insertedCars = 0;
$duplicates = 0;
$failedPages = 0;


// Gå igenom Bilwebs resultatsidor
for (
    $page = 1;
    $page <= $maxPages;
    $page++
) {

    if ($processedCars >= $targetCars) {
        break;
    }


    $pageUrl =
        $baseUrl
        . $page;


    echo "<strong>Sida "
        . $page
        . "</strong> - "
        . htmlspecialchars($pageUrl)
        . "<br>";

    flush();


    // Hämta sidan med cURL
    $ch = curl_init();

    curl_setopt(
        $ch,
        CURLOPT_URL,
        $pageUrl
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
        CURLOPT_ENCODING,
        ""
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

        echo "Kunde inte hämta sida "
            . $page
            . ": "
            . htmlspecialchars(
                curl_error($ch)
            )
            . "<br><br>";

        curl_close($ch);

        $failedPages++;

        usleep(700000);

        continue;
    }


    curl_close($ch);


    // Kontrollera HTTP-status
    if ($httpCode !== 200) {

        echo "Sida "
            . $page
            . " gav HTTP-status "
            . $httpCode
            . "<br><br>";

        $failedPages++;

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

        echo "Kunde inte läsa HTML på sida "
            . $page
            . ".<br><br>";

        $failedPages++;

        usleep(700000);

        continue;
    }


    $xpath =
        new DOMXPath($dom);


    // Hitta alla bilannonser via vehicle-id
    $cards =
        $xpath->query(
            '//*[@data-vehicle-id]'
        );


    if (
        !$cards
        ||
        $cards->length === 0
    ) {

        echo "Inga annonser hittades på sida "
            . $page
            . ". Stoppar scrapern.<br>";

        break;
    }


    echo "Hittade "
        . $cards->length
        . " annonser på sidan.<br>";


    // Gå igenom annonserna
    foreach ($cards as $card) {

        if ($processedCars >= $targetCars) {
            break;
        }


        // Vehicle ID
        $vehicleId =
            (int) $card->getAttribute(
                "data-vehicle-id"
            );


        if ($vehicleId <= 0) {
            continue;
        }


        // Märke
        $brand = "";


        $brandNode =
            $xpath->query(
                './/*[@data-brand-name]',
                $card
            )->item(0);


        if ($brandNode) {

            $brand =
                trim(
                    $brandNode->getAttribute(
                        "data-brand-name"
                    )
                );
        }


        // Modell
        $model = "";


        $modelNode =
            $xpath->query(
                './/*[@data-model-name]',
                $card
            )->item(0);


        if ($modelNode) {

            $model =
                trim(
                    $modelNode->getAttribute(
                        "data-model-name"
                    )
                );
        }


        // Hoppa över annonser utan märke eller modell
        if (
            $brand === ""
            ||
            $model === ""
        ) {

            continue;
        }


        // Annonsens URL
        $url = "";


        $linkNode =
            $xpath->query(
                './/a[@href][1]',
                $card
            )->item(0);


        if ($linkNode) {

            $href =
                trim(
                    $linkNode->getAttribute(
                        "href"
                    )
                );


            if ($href !== "") {

                if (
                    str_starts_with(
                        $href,
                        "/"
                    )
                ) {

                    $url =
                        "https://bilweb.se"
                        . $href;

                } elseif (
                    str_starts_with(
                        $href,
                        "http://"
                    )
                    ||
                    str_starts_with(
                        $href,
                        "https://"
                    )
                ) {

                    $url = $href;
                }
            }
        }


        if ($url === "") {
            continue;
        }


        // Standardvärden
        $modelYear = 0;
        $mileage = 0;
        $gearbox = "";
        $fuelType = "";
        $price = 0;


        // Hämta bilens specifikationer
        $specNodes =
            $xpath->query(
                './/div[contains(@class, "flex-wrap")]
                    /span[normalize-space(.) != ""]',
                $card
            );


        if ($specNodes) {

            foreach (
                $specNodes as $specNode
            ) {

                $spec =
                    trim(
                        preg_replace(
                            '/\s+/',
                            ' ',
                            $specNode->textContent
                        )
                    );


                if ($spec === "") {
                    continue;
                }


                // Årsmodell
                if (
                    $modelYear === 0
                    &&
                    preg_match(
                        '/^(19|20)\d{2}$/',
                        $spec
                    )
                ) {

                    $modelYear =
                        (int) $spec;

                    continue;
                }


                // Miltal
                if (
                    $mileage === 0
                    &&
                    preg_match(
                        '/([\d\s]+)\s*mil\b/iu',
                        $spec,
                        $mileageMatch
                    )
                ) {

                    $mileage =
                        (int) preg_replace(
                            '/\D/',
                            '',
                            $mileageMatch[1]
                        );

                    continue;
                }


                // Växellåda
                if (
                    strcasecmp(
                        $spec,
                        "Automatisk"
                    ) === 0
                ) {

                    $gearbox =
                        "Automatisk";

                    continue;
                }


                if (
                    strcasecmp(
                        $spec,
                        "Manuell"
                    ) === 0
                ) {

                    $gearbox =
                        "Manuell";

                    continue;
                }


                if (
                    strcasecmp(
                        $spec,
                        "Sekventiell"
                    ) === 0
                ) {

                    $gearbox =
                        "Sekventiell";

                    continue;
                }


                // Bränsle
                $knownFuels = [
                    "Bensin",
                    "Diesel",
                    "El",
                    "Hybrid",
                    "Elhybrid",
                    "Laddhybrid",
                    "El/Bensin",
                    "El/Diesel",
                    "Bensin/Etanol",
                    "Etanol",
                    "CNG",
                    "Gas"
                ];


                foreach (
                    $knownFuels as $knownFuel
                ) {

                    if (
                        strcasecmp(
                            $spec,
                            $knownFuel
                        ) === 0
                    ) {

                        $fuelType =
                            $spec;

                        break;
                    }
                }
            }
        }


        // Pris
        // Exempel 249 900 kr, men inte leasingpris per månad
        $priceNodes =
            $xpath->query(
                './/*[not(*) and contains(normalize-space(.), "kr")]',
                $card
            );


        if ($priceNodes) {

            foreach (
                $priceNodes as $priceNode
            ) {

                $priceText =
                    trim(
                        preg_replace(
                            '/\s+/',
                            ' ',
                            $priceNode->textContent
                        )
                    );


                if (
                    preg_match(
                        '/^([\d\s]+)\s*kr$/iu',
                        $priceText,
                        $priceMatch
                    )
                ) {

                    $price =
                        (int) preg_replace(
                            '/\D/',
                            '',
                            $priceMatch[1]
                        );

                    break;
                }
            }
        }


        // Spara bilen i MySQL
        $insertStmt->bind_param(
            "issiiisss",
            $vehicleId,
            $brand,
            $model,
            $modelYear,
            $price,
            $mileage,
            $fuelType,
            $gearbox,
            $url
        );


        if (!$insertStmt->execute()) {

            echo "Databasfel för ID "
                . $vehicleId
                . ": "
                . htmlspecialchars(
                    $insertStmt->error
                )
                . "<br>";

            continue;
        }


        $processedCars++;


        // Kontrollera om bilen var ny eller redan fanns
        if (
            $insertStmt->affected_rows === 1
        ) {

            $insertedCars++;
            $status = "NY";

        } else {

            $duplicates++;
            $status = "FINNS REDAN";
        }


        // Visa progress
        echo $processedCars
            . " / "
            . $targetCars
            . " - "
            . htmlspecialchars($brand)
            . " "
            . htmlspecialchars($model)
            . " - "
            . $modelYear
            . " - "
            . number_format(
                $mileage,
                0,
                ",",
                " "
            )
            . " mil - "
            . (
                $price > 0
                    ? number_format(
                        $price,
                        0,
                        ",",
                        " "
                    ) . " kr"
                    : "pris saknas"
            )
            . " - "
            . $status
            . "<br>";


        flush();
    }


    echo "<br>";

    flush();


    // Kort paus mellan sidorna
    usleep(700000);
}


// Stäng databasfrågan och anslutningen
$insertStmt->close();

$conn->close();


// Slutresultat
echo "<hr>";

echo "<h2>Scraping klar!</h2>";

echo "Annonser behandlade: "
    . $processedCars
    . "<br>";

echo "Nya bilar sparade: "
    . $insertedCars
    . "<br>";

echo "Dubletter hoppades över: "
    . $duplicates
    . "<br>";

echo "Misslyckade sidor: "
    . $failedPages
    . "<br>";

?>