<?php session_start(); 
include_once('/hdd1/clashapp/functions.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

$championData = json_decode(file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/en_US/champion.json'), true);
$championKeys = array_keys($championData['data']);
$randomChampionKey = $championKeys[array_rand($championKeys)];
$randomChampion = $championData['data'][$randomChampionKey];
$championName = $randomChampion['name'];
$imagePath = "/clashapp/data/patch/13.16.1/img/champion/{$randomChampion['image']['full']}";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['champion_input'];
    $isCorrect = strtolower($userInput) === strtolower($championName);
}

?>
<style>
    .correct-border {
        border: 2px solid green !important;
    }
    .incorrect-border {
        border: 2px solid red !important;
    }
    #imageContainer {
        position: relative;
    }
    #fullImage {
        position: absolute;
        opacity: 0;
        transition: opacity 1s ease;
    }
    .animate-border-color {
        transition: border-color 0.5s ease-out;
    }
    #championName {
        transition: opacity 0.5s ease-in;
    }
</style>
<div class="text-center mb-4 flex justify-center flex-col items-center mt-40">
    <h1 class="text-3xl font-bold mb-4"><?= __("Pixel Guesser") ?></h1>
    <canvas id="pixelatedCanvas" width="256" height="256"></canvas>
    <img id="fullImage" src="<?= $imagePath ?>" alt="Full Image" width="256" height="256" class="mt-[-6.5rem]">
    <span class="text-2xl h-4 mt-4 animate-fadeIn opacity-0" id="championName"></span>
    <form method="post" class="text-center flex mt-8" id="championForm" onsubmit="checkChamp(); return false;">
        <input  type="text" 
                name="champion_input"
                class="autofill:text-black text-black border border-2 border-solid border-white p-2 focus:border focus:border-2 focus:border-solid focus:border-white <?php if (isset($isCorrect)) echo $isCorrect ? 'correct-border' : 'incorrect-border'; ?>"
                onkeyup="handleInputKeyUp(event)">
        <button type="submit" class="bg-tag-navy text-white px-4 py-2 border-2 border-solid border-tag-navy">&#10148;</button>
    </form>
    <div class="flex justify-center items-center mt-4">
        <span class="text-xl"><?= __("Difficulty: ") ?></span>
        <select id="difficulty-selector" 
                class="text-center h-8 w-28 align-middle mr-2.5 ml-2.5 text-base bg-[#eee] text-black active:bg-[#ccc] focus:outline-none border-none"
                onchange="setCookie('pixelChamp', this.value); this.disabled = true; location.reload();">
            <option value="easy" <?= (!isset($_COOKIE["pixelChamp"]) || $_COOKIE["pixelChamp"] == "easy") ? "selected disabled hidden" : ""; ?>><?= __("Easy") ?></option>
            <option value="medium" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "medium") ? "selected disabled hidden" : ""; ?>><?= __("Medium") ?></option>
            <option value="hard" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "hard") ? "selected disabled hidden" : ""; ?>><?= __("Hard") ?></option>
        </select>
    </div>
</div>

    <script>
    function handleInputKeyUp(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            checkChamp(); // Trigger the checkChamp function on Enter key press
        } else {
            updateInputBorder(false); // Update the input border when any key is released
        }
    }

    function pixelateImage(imagePath, blockSize) {
        const canvas = document.getElementById("pixelatedCanvas");
        const context = canvas.getContext("2d");
        const image = new Image();

        image.onload = function() {
            const scaledWidth = canvas.width / blockSize;
            const scaledHeight = canvas.height / blockSize;
            context.drawImage(image, 0, 0, scaledWidth, scaledHeight);
            context.mozImageSmoothingEnabled = false;
            context.webkitImageSmoothingEnabled = false;
            context.imageSmoothingEnabled = false;
            context.drawImage(canvas, 0, 0, scaledWidth, scaledHeight, 0, 0, canvas.width, canvas.height);
        };

        image.src = imagePath;
    }

    switch (getCookie("pixelChamp")) {
        case "easy":
            pixelateImage("<?= $imagePath ?>", 25); // Adjust blockSize as needed
            break;
        case "medium":
            pixelateImage("<?= $imagePath ?>", 33); // Adjust blockSize as needed
            break;
        case "hard":
            pixelateImage("<?= $imagePath ?>", 50); // Adjust blockSize as needed
            break;
        default:
            break;
    }
    const userInput = document.querySelector("input[name='champion_input']");
    userInput.select();

    function updateInputBorder(isCorrect) {
        const userInput = document.querySelector("input[name='champion_input']");

        if (isCorrect) {
            userInput.classList.remove("incorrect-border", "animate-border-color");
            userInput.classList.add("correct-border");
        } else {
            userInput.classList.remove("correct-border");
            userInput.classList.add("incorrect-border");
            setTimeout(() => {
                userInput.classList.add("animate-border-color");
                userInput.classList.remove("incorrect-border");
                setTimeout(() => {
                    userInput.classList.remove("animate-border-color");
                }, 500);
            }, 500); // Remove incorrect-border class immediately without fade out
        }
    }



    function checkChamp() {
        const userInput = document.querySelector("input[name='champion_input']");
        const championName = "<?= strtolower($championName) ?>".replace(/'/g, '');
        const userAnswer = userInput.value.toLowerCase().replace(/'/g, '');

        const isCorrect = userAnswer === championName;
        updateInputBorder(isCorrect);

        if (isCorrect) {
            const startTime = performance.now();
            const champName = document.getElementById("championName")
            champName.innerText = "<?= __("Correct Answer: ").$championName ?>";

            function revealFullImage(timestamp) {
                const elapsedTime = timestamp - startTime;
                const progress = Math.min(elapsedTime / 1000, 1);

                document.getElementById("fullImage").style.opacity = progress;

                if (progress < 1) {
                    requestAnimationFrame(revealFullImage);
                } else {
                    champName.classList.replace("opacity-0", "opacity-100")
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            }

            requestAnimationFrame(revealFullImage);
        }
    }
    </script>
<?
include('/hdd1/clashapp/templates/footer.php');
?>