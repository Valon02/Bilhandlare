<?php

require "db.php";
require "filter_helpers.php";

$conn->set_charset("utf8mb4");

$filters =
    getCarFilters();


// Pagination
$page =
    isset($_GET["page"])
        ? max(
            1,
            (int) $_GET["page"]
        )
        : 1;


$limit = 25;

$offset =
    ($page - 1)
    * $limit;


// Bygg WHERE-delen från valda filter
[
    $whereSql,
    $types,
    $params
] = buildCarWhere(
    $filters
);


// Räkna alla matchande bilar
$countStmt =
    $conn->prepare("
        SELECT COUNT(*) AS total
        FROM cars
        $whereSql
    ");


if (!$countStmt) {

    die(
        "Kunde inte räkna bilar: "
        . $conn->error
    );
}


if (!empty($params)) {

    $countStmt->bind_param(
        $types,
        ...$params
    );
}


$countStmt->execute();

$countResult =
    $countStmt->get_result();

$countRow =
    $countResult->fetch_assoc();


$totalCars =
    (int) $countRow["total"];


$countStmt->close();


// Hämta bilar
$sql = "
    SELECT
        vehicle_id,
        brand,
        model,
        model_year,
        registration_number,
        price,
        mileage,
        fuel_type,
        gearbox,
        url,
        created_at
    FROM cars

    $whereSql
";


// Sortering
if (
    $filters["sort"]
    === "mileage_asc"
) {

    $sql .= "
        ORDER BY
            mileage = 0 ASC,
            mileage ASC
    ";

} elseif (
    $filters["sort"]
    === "mileage_desc"
) {

    $sql .= "
        ORDER BY mileage DESC
    ";

} elseif (
    $filters["sort"]
    === "price_asc"
) {

    $sql .= "
        ORDER BY
            price = 0 ASC,
            price ASC
    ";

} elseif (
    $filters["sort"]
    === "price_desc"
) {

    $sql .= "
        ORDER BY price DESC
    ";

} else {

    $sql .= "
        ORDER BY created_at DESC
    ";
}


// Visa 25 bilar åt gången
$sql .= "
    LIMIT ?
    OFFSET ?
";


$searchParams =
    $params;


$searchTypes =
    $types;


$searchParams[] =
    $limit;


$searchParams[] =
    $offset;


$searchTypes .=
    "ii";


// Kör SQL-frågan
$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    die(
        "Kunde inte hämta bilar: "
        . $conn->error
    );
}


$stmt->bind_param(
    $searchTypes,
    ...$searchParams
);


$stmt->execute();

$result =
    $stmt->get_result();


// Antal bilar som visas just nu
$currentShown =
    min(
        $page * $limit,
        $totalCars
    );


// Visa antal resultat
echo "
    <div class='result-count d-flex align-items-center gap-2 mb-3'>

        <strong>
            "
            . formatCount($totalCars)
            . " bilar hittades
        </strong>

        <span class='text-secondary'>
            • Visar "
            . formatCount($currentShown)
            . "
        </span>

    </div>
";


// Inga träffar
if ($totalCars === 0) {

    echo "
        <div class='alert alert-secondary'>

            Inga bilar matchade din sökning.

        </div>
    ";

    $stmt->close();

    $conn->close();

    exit;
}


// Lista bilar
echo "
    <div class='cars-list'>
";


while (
    $car = $result->fetch_assoc()
) {

    echo "
        <div class='car-card card shadow-sm mb-3'>

            <div class='card-body'>
    ";


    // Märke och modell
    echo "
        <h5 class='card-title fw-bold mb-2'>
    ";

    echo
        h($car["brand"])
        . " "
        . h($car["model"]);

    echo "
        </h5>
    ";


    // Bilinformation
    echo "
        <p class='text-secondary mb-2'>
    ";


    // Årsmodell
    if (
        (int) $car["model_year"]
        > 0
    ) {

        echo h(
            (string)
            $car["model_year"]
        );

    } else {

        echo "Årsmodell saknas";
    }


    echo " • ";


    // Miltal
    if (
        (int) $car["mileage"]
        > 0
    ) {

        echo
            number_format(
                (int)
                $car["mileage"],
                0,
                ",",
                " "
            )
            . " mil";

    } else {

        echo "Miltal saknas";
    }


    // Växellåda
    if (
        !empty(
            $car["gearbox"]
        )
    ) {

        echo
            " • "
            . h(
                $car["gearbox"]
            );
    }


    // Bränsle
    if (
        !empty(
            $car["fuel_type"]
        )
    ) {

        echo
            " • "
            . h(
                $car["fuel_type"]
            );
    }


    echo "
        </p>
    ";


    // Registreringsnummer
    echo "
        <p class='mb-2'>

            <strong>
                Reg.nr:
            </strong>
    ";


    if (
        !empty(
            $car[
                "registration_number"
            ]
        )
    ) {

        echo h(
            $car[
                "registration_number"
            ]
        );

    } else {

        echo "Saknas";
    }


    echo "
        </p>
    ";


    // Pris
    echo "
        <h5 class='fw-bold mb-3'>
    ";


    if (
        (int) $car["price"]
        > 0
    ) {

        echo
            number_format(
                (int)
                $car["price"],
                0,
                ",",
                " "
            )
            . " kr";

    } else {

        echo "Pris saknas";
    }


    echo "
        </h5>
    ";


    // Länk till originalannons
    echo "
        <a
            href='"
            . h(
                $car["url"]
            )
            . "'
            target='_blank'
            rel='noopener noreferrer'
            class='btn btn-outline-success btn-sm'
        >
            Visa annons
        </a>
    ";


    echo "
            </div>
        </div>
    ";
}


echo "
    </div>
";


// Visa knappen om fler resultat finns
if (
    $currentShown
    < $totalCars
) {

    echo "
        <div
            class='load-more-wrapper text-center py-4'
        >

            <button
                type='button'
                class='load-more-button btn btn-dark px-4'
            >
                Visa fler annonser
            </button>

        </div>
    ";
}


// Stäng anslutningen
$stmt->close();

$conn->close();

?>