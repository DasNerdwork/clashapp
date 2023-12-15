</div>
<?php if($_SERVER['REQUEST_URI'] == "/"){
    echo '<footer class="fixed -bottom-2 w-full">';
} else { 
    echo '<footer class="relative -bottom-2 w-full">';
} ?>
    <div id="colophon" itemtype="https://schema.org/WPFooter" itemscope="itemscope" itemid="#colophon">
        <div class="text-[#8984a5] text-center text-sm"><p class="text-center"> Copyright © 2022 - <?php echo date("Y"); ?> ClashScout.com | <a class="text-white hover:text-[#d1d1fd]" href="https://dasnerdwork.net/impressum"><?= __("Impressum") ?></a> &amp; <a class="text-white hover:text-[#d1d1fd]" href="https://dasnerdwork.net/Datenschutzerklaerung"><?= __("Datenschutzerklärung") ?></a></p>
    </div>
    <div>
        <div class="text-[#8984a5] text-center text-sm"><?= __("ClashScout.com isn't endorsed by Riot Games and doesn't reflect the views or opinions of Riot Games or anyone officially involved in producing or managing Riot Games properties.") ?>
        <br><?= __("Riot Games, and all associated properties are trademarks or registered trademarks of Riot Games, Inc.") ?></div>
    </div>
    <div class="relative bottom-5 right-2.5 float-right text-[#8984a5] text-xs" id="version">
        <?php 
            exec('cd /hdd1/clashapp && git rev-list --all --count', $output); 
            $createDate = new DateTime("2022-01-28"); // 28.01.2022
            $today = new DateTime("today");
            $difference = $createDate->diff($today);
            echo "v0.".sprintf("%03d", $output[0]).".".($difference->y).".".($difference->m+($difference->y*12)).".".sprintf("%04d", $difference->days).
            "<!-- v0.commitCount.yearsSinceCreate.monthsSinceCreate.daysSinceCreate -->";
        ?>
    </div>
</footer>