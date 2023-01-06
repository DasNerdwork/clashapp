</div>
<footer class="full-footer" id="full-footer">
    <div id="colophon" itemtype="https://schema.org/WPFooter" itemscope="itemscope" itemid="#colophon">
        <div class="text-[#333344] text-center text-sm"><p class="text-center"> Copyright © 2022 DasNerdwork.net | <a class="text-[#565670] hover:text-[#8484b3]" href="https://dasnerdwork.net/impressum">Impressum</a> &amp; <a class="text-[#565670] hover:text-[#8484b3]" href="https://dasnerdwork.net/Datenschutzerklaerung">Datenschutzerklärung</a></p>
    </div>
    <div>
        <div class="text-[#333344] text-center text-sm">DasNerdwork.net isn't endorsed by Riot Games and doesn't reflect the views or opinions of Riot Games or anyone officially involved in producing or managing Riot Games properties.
        <br>Riot Games, and all associated properties are trademarks or registered trademarks of Riot Games, Inc.</div>
    </div>
    <div class="relative bottom-5 right-2.5 float-right opacity-[0.15] text-[10px]" id="version">
        <?php 
            exec('cd /hdd2/clashapp && git rev-list --all --count', $output); 
            $createDate = new DateTime("2022-01-28"); // 28.01.2022
            $today = new DateTime("today");
            $difference = $createDate->diff($today);
            echo "v0.".sprintf("%03d", $output[0]).".".($difference->y+1).".".($difference->m+($difference->y*12)).".".sprintf("%04d", $difference->days).
            "<!-- v0.commitCount.yearsSinceCreate.monthsSinceCreate.daysSinceCreate -->";
        ?>
    </div>
</footer>