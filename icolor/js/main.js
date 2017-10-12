/*
  Copyright Maziar Navabi. Please do not duplicate or use this code without written permission  form Maziar Navabi.
 */
var rotation = 0;
var selections;
var colorDivs = new ColorDivs();
var colorGrid;
var colorPie;
var pieChart;
var pieData = {
    datasets: [{
        data: [
            11,
            16,
            7
        ],
        backgroundColor: [
            "cyan",
            "magenta",
            "yellow"
        ],
        label: '' 
    }],
    labels: []
};

var pieOptions = {
    segmentShowStroke: false,
    animateScale: false,
    responsive: true,
    animation: false

};


$(function () {
    jQuery.fn.rotate = function (degrees) {
        $(this).css({'transform': 'rotate(' + degrees + 'deg)'});
        return $(this);
    };

    drawCircles();

    initPalletes();

    //uncomment this for some debuginfo appearing as a tip
    //$('.tip').tipr(); 

    colorGrid = new ColorGrid(12, -20, 10);

    selections = new Selections();
    selections.assignClickHandler();
    selections.assignModeSelector();


});