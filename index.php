<!DOCTYPE html>
<html lang="sv">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Bilhandlare</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


<form id="searchForm">


    <!-- Header -->
    <header class="bg-white border-bottom">

        <div class="container py-3">

            <div class="row align-items-center g-3">


                <div class="col-12 col-md-3">

                    <h4 class="mb-0 fw-bold">
                        Bilhandlare
                    </h4>

                </div>


                <div class="col-12 col-md-9">

                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        placeholder="Sök märke, modell eller registreringsnummer..."
                        autocomplete="off"
                    >

                </div>


            </div>

        </div>

    </header>



    <!-- Innehåll -->
    <main class="py-5">

        <div class="container">


            <div class="mb-4">

                <h1 class="h2 fw-bold">
                    Sök bilar
                </h1>

                <p class="text-secondary mb-0">
                    Sök eller filtrera bland bilannonserna.
                </p>

            </div>



            <!-- Filter -->
            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <div class="row g-3 align-items-end">


                        <!-- Märke -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="brand"
                                class="form-label fw-semibold"
                            >
                                Märke
                            </label>

                            <select
                                id="brand"
                                name="brand"
                                class="form-select"
                            >

                                <option value="">
                                    Laddar...
                                </option>

                            </select>

                        </div>



                        <!-- Modell -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="model"
                                class="form-label fw-semibold"
                            >
                                Modell
                            </label>

                            <select
                                id="model"
                                name="model"
                                class="form-select"
                            >

                                <option value="">
                                    Laddar...
                                </option>

                            </select>

                        </div>



                        <!-- Årsmodell -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="year"
                                class="form-label fw-semibold"
                            >
                                Årsmodell
                            </label>

                            <select
                                id="year"
                                name="year"
                                class="form-select"
                            >

                                <option value="">
                                    Laddar...
                                </option>

                            </select>

                        </div>



                        <!-- Registreringsnummer -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="registration"
                                class="form-label fw-semibold"
                            >
                                Reg.nr
                            </label>

                            <input
                                type="text"
                                id="registration"
                                name="registration"
                                class="form-control"
                                placeholder="ABC123"
                                autocomplete="off"
                            >

                        </div>



                        <!-- Miltal -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="mileage_range"
                                class="form-label fw-semibold"
                            >
                                Miltal
                            </label>

                            <select
                                id="mileage_range"
                                name="mileage_range"
                                class="form-select"
                            >

                                <option value="">
                                    Laddar...
                                </option>

                            </select>

                        </div>



                        <!-- Bränsle -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="fuel"
                                class="form-label fw-semibold"
                            >
                                Bränsle
                            </label>

                            <select
                                id="fuel"
                                name="fuel"
                                class="form-select"
                            >

                                <option value="">
                                    Laddar...
                                </option>

                            </select>

                        </div>



                        <!-- Växellåda -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="gearbox"
                                class="form-label fw-semibold"
                            >
                                Växellåda
                            </label>

                            <select
                                id="gearbox"
                                name="gearbox"
                                class="form-select"
                            >

                                <option value="">
                                    Laddar...
                                </option>

                            </select>

                        </div>



                        <!-- Sortering -->
                        <div class="col-12 col-md-6 col-lg-3">

                            <label
                                for="sort"
                                class="form-label fw-semibold"
                            >
                                Sortera
                            </label>

                            <select
                                id="sort"
                                name="sort"
                                class="form-select"
                            >

                                <option value="">
                                    Senast tillagd
                                </option>

                                <option value="mileage_asc">
                                    Miltal: lägst först
                                </option>

                                <option value="mileage_desc">
                                    Miltal: högst först
                                </option>

                                <option value="price_asc">
                                    Pris: lägst först
                                </option>

                                <option value="price_desc">
                                    Pris: högst först
                                </option>

                            </select>

                        </div>



                        <!-- Rensa filter -->
                        <div class="col-12">

                            <button
                                type="button"
                                id="resetFilters"
                                class="btn btn-dark"
                            >
                                Rensa filter
                            </button>

                        </div>


                    </div>

                </div>

            </div>



            <!-- Sökresultat -->
            <div id="results">

                <div class="text-secondary">
                    Laddar bilar...
                </div>

            </div>


        </div>

    </main>


</form>



<script>

    // JavaScript används bara för AJAX och uppdatering av sidan

    const form =
        document.getElementById(
            "searchForm"
        );


    const results =
        document.getElementById(
            "results"
        );


    const resetButton =
        document.getElementById(
            "resetFilters"
        );


    const searchInput =
        document.getElementById(
            "search"
        );


    const registrationInput =
        document.getElementById(
            "registration"
        );


    const brandInput =
        document.getElementById(
            "brand"
        );


    const modelInput =
        document.getElementById(
            "model"
        );


    const yearInput =
        document.getElementById(
            "year"
        );


    const mileageInput =
        document.getElementById(
            "mileage_range"
        );


    const fuelInput =
        document.getElementById(
            "fuel"
        );


    const gearboxInput =
        document.getElementById(
            "gearbox"
        );


    const sortInput =
        document.getElementById(
            "sort"
        );


    let currentPage = 1;


    // Hämta formulärets värden
    function getParams() {

        return new URLSearchParams(
            new FormData(form)
        );
    }


    // Uppdatera filter och antal
    function loadFilters() {

        const params =
            getParams();


        fetch(
            "filters.php?"
            + params.toString()
        )

            .then(function (response) {

                return response.json();

            })

            .then(function (data) {

                brandInput.innerHTML =
                    data.brand;

                modelInput.innerHTML =
                    data.model;

                yearInput.innerHTML =
                    data.year;

                mileageInput.innerHTML =
                    data.mileage;

                fuelInput.innerHTML =
                    data.fuel;

                gearboxInput.innerHTML =
                    data.gearbox;

            })

            .catch(function (error) {

                console.error(
                    "Filterfel:",
                    error
                );

            });
    }


    // Hämta sökresultat
    function loadCars(
        page = 1,
        append = false
    ) {

        const params =
            getParams();


        params.set(
            "page",
            page
        );


        fetch(
            "search.php?"
            + params.toString()
        )

            .then(function (response) {

                return response.text();

            })

            .then(function (html) {


                // Ny sökning
                if (!append) {

                    results.innerHTML =
                        html;

                    return;
                }


                // Lägg till nästa sida
                const temp =
                    document.createElement(
                        "div"
                    );


                temp.innerHTML =
                    html;


                // Uppdatera antal som visas
                const newSummary =
                    temp.querySelector(
                        ".result-count"
                    );


                const oldSummary =
                    results.querySelector(
                        ".result-count"
                    );


                if (
                    newSummary
                    &&
                    oldSummary
                ) {

                    oldSummary.innerHTML =
                        newSummary.innerHTML;
                }


                // Ta bort gamla Visa fler-knappen
                const oldLoadMore =
                    results.querySelector(
                        ".load-more-wrapper"
                    );


                if (oldLoadMore) {

                    oldLoadMore.remove();

                }


                // Lägg till nästa bilkort
                const currentList =
                    results.querySelector(
                        ".cars-list"
                    );


                const newList =
                    temp.querySelector(
                        ".cars-list"
                    );


                if (
                    currentList
                    &&
                    newList
                ) {

                    currentList
                        .insertAdjacentHTML(
                            "beforeend",
                            newList.innerHTML
                        );

                }


                // Visa knappen igen om fler bilar finns
                const newLoadMore =
                    temp.querySelector(
                        ".load-more-wrapper"
                    );


                if (newLoadMore) {

                    results.appendChild(
                        newLoadMore
                    );

                }

            })

            .catch(function (error) {

                console.error(
                    "Sökfel:",
                    error
                );

            });
    }


    // Starta om sökningen från första sidan
    function refreshSearch() {

        currentPage = 1;

        loadCars(
            1,
            false
        );

        loadFilters();

    }


    // Sök direkt när text skrivs
    form.addEventListener(
        "input",
        function (event) {

            if (
                event.target.tagName
                === "INPUT"
            ) {

                refreshSearch();

            }

        }
    );


    // Uppdatera när ett filter ändras
    form.addEventListener(
        "change",
        function () {

            refreshSearch();

        }
    );


    // Visa nästa 25 bilar
    results.addEventListener(
        "click",
        function (event) {

            if (
                event.target
                    .classList
                    .contains(
                        "load-more-button"
                    )
            ) {

                currentPage++;

                loadCars(
                    currentPage,
                    true
                );

            }

        }
    );


    // Rensa alla filter
    resetButton.addEventListener(
        "click",
        function () {

            searchInput.value = "";

            registrationInput.value = "";

            brandInput.value = "";

            modelInput.value = "";

            yearInput.value = "";

            mileageInput.value = "";

            fuelInput.value = "";

            gearboxInput.value = "";

            sortInput.value = "";


            refreshSearch();

        }
    );


    // Ladda sidan
    refreshSearch();

</script>


</body>

</html>