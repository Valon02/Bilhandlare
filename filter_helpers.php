<?php

// Hämta alla filter från URL:en
function getCarFilters(): array
{
    return [
        "search" => trim($_GET["search"] ?? ""),
        "brand" => trim($_GET["brand"] ?? ""),
        "model" => trim($_GET["model"] ?? ""),
        "year" => trim($_GET["year"] ?? ""),
        "registration" => trim($_GET["registration"] ?? ""),
        "mileage_range" => trim($_GET["mileage_range"] ?? ""),
        "fuel" => trim($_GET["fuel"] ?? ""),
        "gearbox" => trim($_GET["gearbox"] ?? ""),
        "sort" => trim($_GET["sort"] ?? "")
    ];
}


// Bygger WHERE-delen för sökning och filter
// $exclude används när ett filters egna alternativ ska räknas
function buildCarWhere(
    array $filters,
    ?string $exclude = null
): array {

    $whereSql = "
        WHERE 1 = 1
    ";

    $params = [];
    $types = "";


    // Stor sökruta: märke, modell eller reg.nr
    if ($filters["search"] !== "") {

        $textSearch =
            "%" . strtolower($filters["search"]) . "%";


        $registrationSearch =
            preg_replace(
                '/[\s-]+/',
                '',
                strtoupper($filters["search"])
            );

        $registrationSearch .= "%";


        $whereSql .= "
            AND (
                LOWER(
                    CONCAT_WS(
                        ' ',
                        brand,
                        model
                    )
                ) LIKE ?

                OR

                LOWER(
                    CONCAT_WS(
                        ' ',
                        model,
                        brand
                    )
                ) LIKE ?

                OR

                registration_number LIKE ?
            )
        ";


        $params[] = $textSearch;
        $params[] = $textSearch;
        $params[] = $registrationSearch;

        $types .= "sss";
    }


    // Märke
    if (
        $exclude !== "brand"
        &&
        $filters["brand"] !== ""
    ) {

        $whereSql .= "
            AND brand = ?
        ";

        $params[] = $filters["brand"];
        $types .= "s";
    }


    // Modell
    if (
        $exclude !== "model"
        &&
        $filters["model"] !== ""
    ) {

        $whereSql .= "
            AND model = ?
        ";

        $params[] = $filters["model"];
        $types .= "s";
    }


    // Årsmodell
    if (
        $exclude !== "year"
        &&
        $filters["year"] !== ""
        &&
        is_numeric($filters["year"])
    ) {

        $whereSql .= "
            AND model_year = ?
        ";

        $params[] =
            (int) $filters["year"];

        $types .= "i";
    }


    // Registreringsnummer
    if (
        $exclude !== "registration"
        &&
        $filters["registration"] !== ""
    ) {

        $registration =
            preg_replace(
                '/[\s-]+/',
                '',
                strtoupper(
                    $filters["registration"]
                )
            );

        $registration .= "%";


        $whereSql .= "
            AND registration_number LIKE ?
        ";

        $params[] = $registration;
        $types .= "s";
    }


    // Miltal
    if ($exclude !== "mileage") {

        $range =
            $filters["mileage_range"];


        if ($range === "0-5000") {

            $whereSql .= "
                AND mileage > 0
                AND mileage <= 5000
            ";

        } elseif ($range === "5000-10000") {

            $whereSql .= "
                AND mileage > 5000
                AND mileage <= 10000
            ";

        } elseif ($range === "10000-15000") {

            $whereSql .= "
                AND mileage > 10000
                AND mileage <= 15000
            ";

        } elseif ($range === "15000-20000") {

            $whereSql .= "
                AND mileage > 15000
                AND mileage <= 20000
            ";

        } elseif ($range === "20000+") {

            $whereSql .= "
                AND mileage > 20000
            ";
        }
    }


    // Bränsle
    if (
        $exclude !== "fuel"
        &&
        $filters["fuel"] !== ""
    ) {

        $whereSql .= "
            AND fuel_type = ?
        ";

        $params[] = $filters["fuel"];
        $types .= "s";
    }


    // Växellåda
    if (
        $exclude !== "gearbox"
        &&
        $filters["gearbox"] !== ""
    ) {

        $whereSql .= "
            AND gearbox = ?
        ";

        $params[] =
            $filters["gearbox"];

        $types .= "s";
    }


    return [
        $whereSql,
        $types,
        $params
    ];
}


// Kör en prepared SELECT och returnerar resultatet som array
function fetchRows(
    mysqli $conn,
    string $sql,
    string $types = "",
    array $params = []
): array {

    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        die(
            "SQL-fel: "
            . $conn->error
        );
    }


    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }


    $stmt->execute();

    $result =
        $stmt->get_result();


    $rows = [];

    while (
        $row = $result->fetch_assoc()
    ) {

        $rows[] = $row;
    }


    $stmt->close();


    return $rows;
}


// Skyddar text som skrivs ut i HTML
function h(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


// Formaterar antal med mellanslag som tusentalsavgränsare
function formatCount(int $number): string
{
    return number_format(
        $number,
        0,
        ",",
        " "
    );
}