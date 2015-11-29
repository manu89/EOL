<?php
/**
 * File: index.php
 * User: Masterplan
 * Date: 3/21/13
 * Time: 8:44 PM
 * Desc: Admin's Homepage
 */

global $config, $user;

?>

<div id="navbar">
    <?php printMenu(); ?>
</div>

<div id="main">
    <!--<div class="clearer"></div>-->
    <div id="assesmentCustomize">

        <?php
        openBox(ttAssesmentOverview, 'normal', 'report');

        ?>
            <h1><?=ttReportCustomize?></h1>
            <hr>
            <h3><?=ttReportAssessmentInformation?></h3>
            <hr>
            <form name="template" action="" method="post">
            <div class="templatecontent">
            <table class="customize">
                <tr>
                    <td class="bold title"><?=ttReportField?></td>
                    <td class="bold title"><?=ttReportChecked?></td>
                    <td class="bold title"><?=ttReportOrder?></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentName?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentID?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentAuthor?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentDateTimeFirst?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentDateTimeLast?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentNumberStarted?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentNumberNotFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentNumberFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentMinscoreFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentMaxcoreFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentMediumFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentLeastTimeFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentMostTimeFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentMediumTimeFinished?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td><?=ttReportAssesmentStdDeviation?></td>
                    <td><input type="checkbox"></td>
                    <td><input type="number" id="" class="order"></td>
                </tr>
                <tr>
                    <td></td>
                    <td><a class="normal button" id="selectall" onclick=""><?=ttSelectAll?></td>
                    <td><a class="normal button" id="resetorder" onclick=""><?=ttResetOrder?></td>
                </tr>
            </table>

            </div>

                <h3><?=ttReportTopicInformation?></h3>
                <hr>
                <form name="template" action="" method="post">
                    <div class="templatecontent">
                        <table class="customize">
                            <tr>
                                <td class="bold title"><?=ttReportField?></td>
                                <td class="bold title"><?=ttReportChecked?></td>
                                <td class="bold title"><?=ttReportOrder?></td>
                            </tr>
                            <tr>
                                <td><?=ttReportTopicAverageScore?></td>
                                <td><input type="checkbox"></td>
                                <td><input type="number" id="" class="order"></td>
                            </tr>
                            <tr>
                                <td><?=ttReportTopicMinimumScore?></td>
                                <td><input type="checkbox"></td>
                                <td><input type="number" id="" class="order"></td>
                            </tr>
                            <tr>
                                <td><?=ttReportTopicMaximumScore?></td>
                                <td><input type="checkbox"></td>
                                <td><input type="number" id="" class="order"></td>
                            </tr>
                            <tr>
                                <td><?=ttReportTopicStandardDeviation?></td>
                                <td><input type="checkbox"></td>
                                <td><input type="number" id="" class="order"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><a class="normal button" id="selectall" onclick=""><?=ttSelectAll?></td>
                                <td><a class="normal button" id="resetorder" onclick=""><?=ttResetOrder?></td>
                            </tr>
                        </table>

                    </div>

                    <h3><?=ttReportGraphicalDsiplays?></h3>
                    <hr>
                    <form name="template" action="" method="post">
                        <div class="templatecontent">
                            <table class="customize">
                                <tr>
                                    <td class="bold title"><?=ttReportField?></td>
                                    <td class="bold title"><?=ttReportChecked?></td>
                                    <td class="bold title"><?=ttReportOrder?></td>
                                </tr>
                                <tr>
                                    <td><?=ttReportTopicAverageScore?></td>
                                    <td><input type="checkbox"></td>
                                    <td><input type="number" id="" class="order"></td>
                                </tr>
                                <tr>
                                    <td><?=ttReportTopicMinimumScore?></td>
                                    <td><input type="checkbox"></td>
                                    <td><input type="number" id="" class="order"></td>
                                </tr>
                                <tr>
                                    <td><?=ttReportTopicMaximumScore?></td>
                                    <td><input type="checkbox"></td>
                                    <td><input type="number" id="" class="order"></td>
                                </tr>
                                <tr>
                                    <td><?=ttReportTopicStandardDeviation?></td>
                                    <td><input type="checkbox"></td>
                                    <td><input type="number" id="" class="order"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><a class="normal button" id="selectall" onclick=""><?=ttSelectAll?></td>
                                    <td><a class="normal button" id="resetorder" onclick=""><?=ttResetOrder?></td>
                                </tr>
                            </table>
                            <br>
                            <a class="normal button done" onclick="template.submit()"><?=ttSend?></a>
                        </div>
                        <br/>

            </form>
        <?php
        closeBox();
        ?>

    </div>
    <div class="clearer"></div>
</div>