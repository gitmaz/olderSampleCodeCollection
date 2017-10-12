/*
 Copyright Maziar Navabi. Please do not duplicate or use this code without written permission form Maziar Navabi.
 */


var darkness = 0;
function ColorMixer() {

    this.wContributionStr = '256,256,256';
    this.bkNormalContributionStr = '-8,-8,-8';
    this.rNormalContributionStr = '0,-8,-8';
    this.bNormalContributionStr = '-8,-8,0';
    this.yNormalContributionStr = '0,0,-8';
    this.oNormalContributionStr = '0,1,-8';
    this.gNormalContributionStr = '-8,0,-8';
    this.pNormalContributionStr = '0,-4,0';
    this.rDevision = this.bDevision = this.yDevision = 0;
    this.rybComp = new Array(0, 0, 0);

    ColorMixer.prototype.setRybComps = function (r, y, b) {
        this.rybComp[0] = r;
        this.rybComp[1] = y;
        this.rybComp[2] = b;

    };

    ColorMixer.prototype.paintRyb = function (r, y, b, resultDivId) {

        this.setRybComps(r, y, b);

        this.calculateDivisions();

        this.paint(resultDivId);
    };


    ColorMixer.prototype.paint = function (resultDivId) {


        this.resultV = new this.RgbVector(this.wContributionStr);
        this.rbyDevisions = this.rDevision + this.bDevision + this.yDevision;
        if (this.rbyDevisions > 0) {
            var rContributionV = new this.RgbVector(this.rNormalContributionStr);
            rContributionV.scale(this.rDevision * this.rDevision / this.rbyDevisions);
            this.resultV.add(rContributionV);
            var bContributionV = new this.RgbVector(this.bNormalContributionStr);
            bContributionV.scale(this.bDevision * this.bDevision / this.rbyDevisions);
            this.resultV.add(bContributionV);
            var yContributionV = new this.RgbVector(this.yNormalContributionStr);
            yContributionV.scale(this.yDevision * this.yDevision / this.rbyDevisions);
            this.resultV.add(yContributionV);
            var oContributionV = new this.RgbVector(this.oNormalContributionStr);
            oContributionV.scale((this.rDevision * this.yDevision) / 32);
            this.resultV.add(oContributionV);
            var gContributionV = new this.RgbVector(this.gNormalContributionStr);
            gContributionV.scale((this.bDevision * this.yDevision) / 32);
            this.resultV.add(gContributionV);
            var pContributionV = new this.RgbVector(this.pNormalContributionStr);
            pContributionV.scale((this.bDevision * this.rDevision) / 32);
            this.resultV.add(pContributionV);
            var bkContributionV = new this.RgbVector(this.bkNormalContributionStr);
            bkContributionV.scale((this.bDevision * this.rDevision * this.yDevision) / 1024);
            this.resultV.add(bkContributionV);
        }

        this.resultV.max(255);
        this.resultV.min(0);
        this.resultV.roundoff();
        cellToPaint = document.getElementById(resultDivId);

        if (cellToPaint != null) {
            cellToPaint.style.backgroundColor = 'rgb(' +
                this.resultV.value[0] + ',' +
                this.resultV.value[1] + ',' +
                this.resultV.value[2] + ')';
        }
    };

    ColorMixer.prototype.rendercolor = function (resultDivId) {

        document.getElementById('rd_' + resultDivId).innerHTML = this.rybComp[0] / 32;
        document.getElementById('bl_' + resultDivId).innerHTML = this.rybComp[2] / 32;
        document.getElementById('ye_' + resultDivId).innerHTML = this.rybComp[1] / 32;

        this.paint(resultDivId);

        document.getElementById('hexa_' + resultDivId).innerHTML = (this.componentToHex(this.resultV.value[0]) +
        this.componentToHex(this.resultV.value[1]) +
        this.componentToHex(this.resultV.value[2]));
    };

    ColorMixer.prototype.change = function (resultDivId, r, y, b) {

        this.addPure(r, y, b);
        this.rendercolor(resultDivId);
    };

    ColorMixer.prototype.addPure = function (r, y, b) {
        this.rybComp[0] += r;
        this.rybComp[1] += y;
        this.rybComp[2] += b;

        this.calculateDivisions();

    };

    ColorMixer.prototype.calculateDivisions = function () {
        var i = Math.max(this.rybComp[0], this.rybComp[1], this.rybComp[2]);
        if (i > 32) i = 32 / i;
        else i = 1;
        this.rDevision = Math.round(this.rybComp[0] * i);

        this.yDevision = Math.round(this.rybComp[1] * i);
        this.bDevision = Math.round(this.rybComp[2] * i);
    };


    ColorMixer.prototype.addUp = function (resultDivId, rDevision1, yDevision1, bDevision1, rDevision2, yDevision2, bDevision2) {

        this.clearpalette(resultDivId);

        this.rybComp[0] = rDevision1 + rDevision2;
        this.rybComp[1] = yDevision1 + yDevision2;
        this.rybComp[2] = bDevision1 + bDevision2;

        this.calculateDivisions();

        this.rendercolor(resultDivId);
    };

    ColorMixer.prototype.clearpalette = function (resultDivId) {
        this.rybComp[0] = this.rybComp[1] = this.rybComp[2] = this.rDevision = this.yDevision = this.bDevision = 0;
        this.rendercolor(resultDivId);
    };

    ColorMixer.prototype.componentToHex = function (c) {
        var hex = c.toString(16);
        return hex.length == 1 ? "0" + hex : hex;
    };

    ColorMixer.prototype.RgbVector = function (bNormalContributionStr) {
        var y = bNormalContributionStr.split(',');
        this.value = [];

        for (var x = 0; x < y.length; x++) {
            if (Number(y[x]) == y[x]) {
                this.value.push(Number(y[x]));
            }
        }
    };
    ColorMixer.prototype.RgbVector.prototype.roundoff = function () {

        for (var x = this.value.length - 1; x >= 0; x--) {
            this.value[x] = Math.round(this.value[x]);
        }
    };

    ColorMixer.prototype.RgbVector.prototype.min = function (i) {
        for (var x = this.value.length - 1; x >= 0; x--) {
            if (this.value[x] < i) this.value[x] = i;
        }
    };

    ColorMixer.prototype.RgbVector.prototype.max = function (i) {
        for (var x = this.value.length - 1; x >= 0; x--) {
            if (this.value[x] > i) this.value[x] = i;
        }
    };

    ColorMixer.prototype.RgbVector.prototype.add = function (rgbVector) {
        for (var x = 0; x < Math.min(this.value.length, rgbVector.value.length); x++) {
            this.value[x] += rgbVector.value[x];
        }
    };

    ColorMixer.prototype.RgbVector.prototype.scale = function (factor) {
        for (var x = this.value.length - 1; x >= 0; x--) {
            this.value[x] *= factor;
        }
    };
}

function CmykColorMixer() {

    CmykColorMixer.prototype.paintCmyk = function (C, M, Y, K, resultDivId) {

        this.R = Math.round(255 * (1 - C) * (1 - K));
        this.G = Math.round(255 * (1 - M) * (1 - K));
        this.B = Math.round(255 * (1 - Y) * (1 - K));

        this.paint(resultDivId);
    };

    CmykColorMixer.prototype.paint = function (resultDivId) {


        cellToPaint = document.getElementById(resultDivId);

        if (cellToPaint != null) {
            cellToPaint.style.backgroundColor = 'rgb(' +
                this.R + ',' +
                this.G + ',' +
                this.B + ')';
        }
    };


}


function ColorDiv(id) {
    this.r = 0;
    this.y = 0;
    this.b = 0;
    this.rDiv = 0;
    this.yDiv = 0;
    this.bDiv = 0;
    this.kDiv = 0;
    this.rDivComp = 0;
    this.yDivComp = 0;
    this.bDivComp = 0;
    this.id = id;

    ColorDiv.prototype.setColor = function (r, y, b) {
        this.r = r;
        this.y = y;
        this.b = b;
    };

    ColorDiv.prototype.setColorByDivisions = function (rDiv, yDiv, bDiv, kDiv) {
        this.rDiv = rDiv;
        this.yDiv = yDiv;
        this.bDiv = bDiv;
        if (kDiv == null) {
            this.kDiv = 0;
        }
        else {
            this.kDiv = kDiv;
        }
    };

    ColorDiv.prototype.setColorComplementByDivisions = function (rDiv, yDiv, bDiv) {
        this.rDivComp = rDiv;
        this.yDivComp = yDiv;
        this.bDivComp = bDiv;
    };
}

function ColorDivs() {
    this.divs = [];
    this.divsBase = [];

    ColorDivs.prototype.setColor = function (divId, r, y, b) {
        this.divs[divId].setColor(r, y, b);
    };

    ColorDivs.prototype.setColorByDivisions = function (divId, rDiv, yDiv, bDiv, kDiv) {
        this.divs[divId] = new ColorDiv(divId);
        this.divs[divId].setColorByDivisions(rDiv, yDiv, bDiv, kDiv);
    };

    ColorDivs.prototype.setBaseColorByDivisions = function (divId, rDiv, yDiv, bDiv) {
        this.divsBase[divId] = new ColorDiv(divId);
        this.divsBase[divId].setColorByDivisions(rDiv, yDiv, bDiv);
    };

    ColorDivs.prototype.setColorComplementByDivisions = function (divId, rDiv, yDiv, bDiv) {
        this.divs[divId].setColorComplementByDivisions(rDiv, yDiv, bDiv);
    };

    ColorDivs.prototype.getDivRYB = function (cellDivId) {
        colorDivId = cellDivId;//.substr(16,6);

        var cellDivColor = {R: 0, Y: 0, B: 0};

        cellDivColor.R = colorDivs.divs[colorDivId].rDiv;
        cellDivColor.Y = colorDivs.divs[colorDivId].yDiv;
        cellDivColor.B = colorDivs.divs[colorDivId].bDiv;

        return cellDivColor;
    };

    ColorDivs.prototype.getDivCmyk = function (cellDivId) {
        colorDivId = cellDivId;//.substr(16,6);

        var cellDivColor = {R: 0, Y: 0, B: 0, K: 0};

        cellDivColor.R = colorDivs.divs[colorDivId].rDiv;
        cellDivColor.Y = colorDivs.divs[colorDivId].yDiv;
        cellDivColor.B = colorDivs.divs[colorDivId].bDiv;
        cellDivColor.K = colorDivs.divs[colorDivId].kDiv;

        return cellDivColor;
    };

    ColorDivs.prototype.getBaseDivRYB = function (cellDivId) {
        colorDivId = cellDivId;//.substr(16,6);

        var cellDivColor = {R: 0, Y: 0, B: 0};

        cellDivColor.R = colorDivs.divsBase[colorDivId].rDiv;
        cellDivColor.Y = colorDivs.divsBase[colorDivId].yDiv;
        cellDivColor.B = colorDivs.divsBase[colorDivId].bDiv;

        return cellDivColor;
    };

}


function Selections() {
    Selections.prototype.selectionCount = 0;
    Selections.prototype.selectionStyle = ['5px double yellow', '5px double red', '5px double blue'];
    Selections.prototype.selectedDivIds = [];
    Selections.prototype.curDivColor = {};
    Selections.prototype.selDivId = null;
    Selections.prototype.selDivIdFromColorPicks = null;
    Selections.prototype.mouseClickMode = "single";
    Selections.prototype.selDivIdFromGaugeColors = 'divGaugeColor2';
    $('#' + Selections.prototype.selDivIdFromGaugeColors).css({'border': '2px dashed yellow'});


    Selections.prototype.assignClickHandler = function () {

        $('.tip').click(function () {

            //if($(this).hasClass('gradient')){
            if (Selections.prototype.mouseClickMode == "single") {

                if (Selections.prototype.selDivId != null) {
                    $('#' + Selections.prototype.selDivId).css({'border-style': 'none'});
                }

                Selections.prototype.selDivId = $(this).attr('id');
                Selections.prototype.selDivId = Selections.prototype.selDivId;
                $(this).css({'border': Selections.prototype.selectionStyle[0]});
                $('#' + Selections.prototype.selDivIdFromColorPicks).css('background-color', $('#' + Selections.prototype.selDivId).css('background-color'));
                /* if($(this).hasClass('gradient')){
                 Selections.prototype.updateCmykColorGaugeFromGradient(Selections.prototype.selDivIdFromGaugeColors);
                 }*/

                Selections.prototype.updateCmykColorGaugeFromCircle(Selections.prototype.selDivIdFromGaugeColors);

                return;

            }


            if (Selections.prototype.selectionCount == 0) {
                $('.tip').css({'border-style': 'none'});
                Selections.prototype.selectedDivIds = [];
            }

            $(this).css({'border': Selections.prototype.selectionStyle[Selections.prototype.selectionCount]});
            Selections.prototype.selDivId = $(this).attr('id');
            Selections.prototype.selectedDivIds[Selections.prototype.selectionCount] = Selections.prototype.selDivId;

            $('#' + Selections.prototype.selDivIdFromColorPicks).css('background-color', $('#' + Selections.prototype.selDivId).css('background-color'));


            if (Selections.prototype.selectionCount < 3) {

                Selections.prototype.updateCmykColorGaugeFromCircle(Selections.prototype.selDivIdFromGaugeColors);


                Selections.prototype.selectionCount++;
                if (Selections.prototype.selectionCount == 3) {
                    //colorGrid.fillGradient(Selections.prototype.selectedDivIds[0],Selections.prototype.selectedDivIds[1],Selections.prototype.selectedDivIds[2]);
                    colorGrid.fillCmykGradient(Selections.prototype.selectedDivIds[0], Selections.prototype.selectedDivIds[1], Selections.prototype.selectedDivIds[2]);

                    Selections.prototype.selectionCount = 0;

                }

            } else {


            }

        });

        $('.colorPick').click(function () {


            var thisDivId = $(this).attr('id');
            if (thisDivId == Selections.prototype.selDivIdFromColorPicks) {
                Selections.prototype.selDivIdFromColorPicks = null;
                $('#' + thisDivId).css({'border-style': 'none'});
                return;
            }


            if (Selections.prototype.selDivIdFromColorPicks != null) {
                $('#' + Selections.prototype.selDivIdFromColorPicks).css({'border-style': 'none'});
            }

            Selections.prototype.selDivIdFromColorPicks = thisDivId;

            $(this).css({'border': '2px dashed yellow'});


        });

        $('.divGaugeColor').click(function () {
            /*var bgc=$(this).css('background-color');
             $('body').css('background-color',bgc);
             $('#divMainPanel').css('background-color',bgc);
             $('#palletes').css('background-color',bgc);
             $('#divColorCirclePanel').css('background-color',bgc);
             $('#divGridPanel').css('background-color',bgc);
             $('#colorGrid').css('background-color',bgc);*/
            if (Selections.prototype.selDivIdFromGaugeColors != null) {
                $('#' + Selections.prototype.selDivIdFromGaugeColors).css({'border-style': 'none'});
            }
            Selections.prototype.selDivIdFromGaugeColors = $(this).attr('id');

            $(this).css({'border': '2px dashed yellow'});
        });
    };

    Selections.prototype.assignModeSelector = function () {
        $(".radios-mouse-selection-mode").click(function () {
            Selections.prototype.mouseClickMode = $(this).val();
            //alert(Selections.prototype.mouseClickMode);

            //if(Selections.prototype.mouseClickMode=="single") {
            Selections.prototype.selectionCount = 0;
            $('.tip').css({'border-style': 'none'});
            Selections.prototype.selectedDivIds = [];
            //}
        });
    };

    Selections.prototype.updateCmykColorGaugeFromCircle = function (divGaugeColorId) {

        Selections.prototype.getCurDivColorCmyk();
        this.updateCmykColorGauge(divGaugeColorId, Selections.prototype.curDivColor)
        this.updateRgbColorGauge(divGaugeColorId, Selections.prototype.curDivColor.R, Selections.prototype.curDivColor.Y, Selections.prototype.curDivColor.B)

    };

    Selections.prototype.updateCmykColorGaugeFromGradient = function (divGaugeColorId) {

        Selections.prototype.getCurDivColor();
        this.updateCmykColorGauge(divGaugeColorId, Selections.prototype.curDivColor)
        this.updateRgbColorGauge(divGaugeColorId, Selections.prototype.curDivColor.R, Selections.prototype.curDivColor.Y, Selections.prototype.curDivColor.B)
        this.updateOgpColorGauge(divGaugeColorId, Selections.prototype.curDivColor.R, Selections.prototype.curDivColor.Y, Selections.prototype.curDivColor.B)

    };

    Selections.prototype.updateCmykColorGauge = function (divGaugeColorId, cmykColor) {

        if (cmykColor.K == null) {
            cmykColor.K = 0;
        }

        this.drawPie(cmykColor.R, cmykColor.Y, cmykColor.B, cmykColor.K);
        //this.drawRybBars(1,r,y,b);
        this.drawCmykBars(1, cmykColor.R, cmykColor.Y, cmykColor.B, cmykColor.K);
        color = $('#' + Selections.prototype.selDivId).css('background-color');
        $('#' + divGaugeColorId).css('background-color', color);
    };


    Selections.prototype.updateRgbColorGauge = function (divGaugeColorId, r, y, b) {

        //this.drawPie(r,y,b);

        var rybArray = [];
        rybArray[0] = r;
        rybArray[1] = y;
        rybArray[2] = b;


        rybArray.sort(function (a, b) {
            return a - b
        });
        var medianValue = rybArray[1];

        var medianIndex = -1;
        if (medianValue == b) {
            medianIndex = 2;
        } else if (medianValue == y) {
            medianIndex = 1;
        } else if (medianValue == r) {
            medianIndex = 0;
        }

        var minValue = rybArray[0];
        var minIndex = -1;
        if (minValue == r) {
            minIndex = 0;
        } else if (minValue == y) {
            minIndex = 1;
        } else if (minValue == b) {
            minIndex = 2;
        }

        var maxValue = rybArray[2];
        var maxIndex = -1;
        if (maxValue == r) {
            maxIndex = 0;
        } else if (maxValue == y) {
            maxIndex = 1;
        } else if (maxValue == b) {
            maxIndex = 2;
        }


        mixtureIndex = [[-1, 0, 2],
            [0, -1, 1],
            [2, 1, -1]];

        var maxAndMedianOverlapValue = (medianValue) * 2;
        var maxAndMedianOverlapIndex = mixtureIndex[maxIndex][medianIndex];


        if (medianValue == maxValue && minValue == 0) {
            var orange = 0;
            var purple = 0;
            var green = 0;
            switch (maxAndMedianOverlapIndex) {
                case 0:
                    orange = maxAndMedianOverlapValue;
                    break;
                case 1:
                    green = maxAndMedianOverlapValue;
                    break;
                case 2:
                    purple = maxAndMedianOverlapValue;
                    break;
            }

            this.drawRgbBars(1, orange, green, purple, maxIndex, 0);

            return;
        }

        var maxAndMedianresedualValue = (maxValue - medianValue);
        var maxAndMedianresedualIndex = maxIndex;

        var minAndResedualOverlapValue = 2 * Math.min(maxAndMedianresedualValue, minValue);
        var minAndResedualOverlapIndex = mixtureIndex[minIndex][maxIndex];

        var minAndResedualResedualValue = 0;
        var minAndResedualResedualIndex = -1;

        if (minValue > 0) {
            if (maxAndMedianresedualValue > minValue) {
                minAndResedualResedualIndex = maxIndex;
                minAndResedualResedualValue = maxAndMedianresedualValue - minValue;

            }
            else {
                minAndResedualResedualIndex = minIndex;
                minAndResedualResedualValue = minValue - maxAndMedianresedualValue;
            }
        }

        var orange = 0;
        var purple = 0;
        var green = 0;
        switch (maxAndMedianOverlapIndex) {
            case 0:
                orange = maxAndMedianOverlapValue;
                break;
            case 1:
                green = maxAndMedianOverlapValue;
                break;
            case 2:
                purple = maxAndMedianOverlapValue;
                break;
        }


        if (minValue > 0) {
            switch (minAndResedualOverlapIndex) {
                case 0:
                    orange = minAndResedualOverlapValue;
                    break;
                case 1:
                    green = minAndResedualOverlapValue;
                    break;
                case 2:
                    purple = minAndResedualOverlapValue;
                    break;
            }

        }
        if (minAndResedualResedualValue > 0) {
            // alert(minAndResedualResedualIndex);
        }

        this.drawRgbBars(1, orange, green, purple, (minAndResedualResedualValue == 0 ? maxIndex : minAndResedualResedualIndex), (minAndResedualResedualValue == 0 ? maxAndMedianresedualValue : minAndResedualResedualValue));

    };
    Selections.prototype.updateOgpColorGauge = function (divGaugeColorId, C, M, Y) {

        
        Oc = 0;
        Om = 0.75;
        Oy = 1;

        Gc = 0.43;
        Gm = 0.23;
        Gy = 0.43;

        Pc = 0.2;
        Pm = 0.8;
        Py = 0;

        var orange = (M - (Y / Gy) * (Gm - Gc * Pm / Pc) - C * Pm / Pc) / (Om - Oy * Gm / Gy - Oy * Gc * Pm / Pc * Gy);
        var green = (Y - orange * Oy) / Gy;
        var purple = (C - green * Gc) / Pc;

        if (orange < 0 || green < 0 || purple < 0) {
            orange = 0;
            green = 0;
            purple = 0;
        }

        this.drawOgpBars(6, orange, green, purple);

    };

    Selections.prototype.drawRybBars = function (groupNr, r, y, b) {
        var tot = r + y + b;
        var rDiv = (r * 100) / tot;
        var yDiv = (y * 100) / tot;
        var bDiv = (b * 100) / tot;
        $("#divRedProgress" + groupNr).css("width", rDiv + "%");
        $("#divYellowProgress" + groupNr).css("width", yDiv + "%");
        $("#divBlueProgress" + groupNr).css("width", bDiv + "%");

        this.setRybNumerics(rDiv, yDiv, bDiv);
    };

    Selections.prototype.drawOgpBars = function (groupNr, o, g, p) {
        var tot = o + g + p;
        var oDiv = (o * 100) / tot;
        var gDiv = (g * 100) / tot;
        var pDiv = (p * 100) / tot;
        $("#divOrangeProgress" + groupNr).css("width", oDiv + "%");
        $("#divGreenProgress" + groupNr).css("width", gDiv + "%");
        $("#divPurpleProgress" + groupNr).css("width", pDiv + "%");

        this.setOgpNumerics(oDiv, gDiv, pDiv);
    };

    Selections.prototype.drawCmykBars = function (groupNr, c, m, y, k) {
        var tot = c + m + y + k;
        var cDiv = (c * 100) / tot;
        var mDiv = (m * 100) / tot;
        var yDiv = (y * 100) / tot;
        var kDiv = (k * 100) / tot;
        $("#divCyanProgress" + groupNr).css("width", cDiv + "%");
        $("#divMagentaProgress" + groupNr).css("width", mDiv + "%");
        $("#divYellowProgress3").css("width", yDiv + "%");
        $("#divBlackProgress").css("width", kDiv + "%");

        this.setRybNumerics(cDiv, mDiv, yDiv);
    };

    Selections.prototype.drawRgbBars = function (groupNr, o, g, p, resedualInd, resedualValue) {

        var tot = o + g + p + resedualValue;
        var oDiv = (o * 100) / tot;
        var gDiv = (g * 100) / tot;
        var pDiv = (p * 100) / tot;
        var resDiv = (resedualValue * 100) / tot;

        $('#spnX2').html("x");
        $("#divPickO3").html(0);
        $("#divPickG3").html(0);
        $("#divPickP3").html(0);

        var residualName = ['c', 'm', 'y'];
        var residualFullName = ['red', 'yellow', 'blue'];

        $("#divBlueProgress4").css("width", oDiv + "%");
        $("#divRedProgress4").css("width", gDiv + "%");
        $("#divGreenProgress4").css("width", pDiv + "%");
        $("#divCyanProgress5").css("width", "0%");
        $("#divMagentaProgress5").css("width", "0%");
        $("#divYellowProgress5").css("width", "0%");
        // $("#divBlackProgress").css("width","0%");

        var ogpValues = [o, g, p];
        var alreadyUsedInd = -1;

        $('#spnX1').html("x");
        if (resedualInd > -1) {
            var resName = residualFullName[resedualInd];
            $("#divOrangeProgress1").css("width", oDiv + "%");

            switch (resedualInd) {
                case 0:
                    $('#spnX1').html(residualName[resedualInd]);
                    alreadyUsedInd = 0;
                    $("#divCyanProgress5").css("width", resDiv + "%");
                    break;
                case 1:
                    $('#spnX1').html(residualName[resedualInd]);
                    alreadyUsedInd = 1;
                    $("#divMagentaProgress5").css("width", resDiv + "%");
                    break;
                case 2:
                    $('#spnX1').html(residualName[resedualInd]);
                    alreadyUsedInd = 2;
                    $("#divYellowProgress5").css("width", resDiv + "%");
                    break;
            }

        }
        else {

            if (p == 0) {
                if (resedualValue > 0) {
                    $('#spnX1').html(residualName[resedualInd]);
                    $("#divYellowProgress5").css("width", resDiv + "%");
                }
            } else if (g == 0) {
                if (resedualValue > 0) {
                    $('#spnX1').html(residualName[resedualInd]);
                    $("#divMagentaProgress5").css("width", resDiv + "%");
                }
            } else if (o == 0) {
                if (resedualValue > 0) {
                    $('#spnX1').html(residualName[resedualInd]);
                    $("#divCyanProgress5").css("width", resDiv + "%");
                }
            }
        }


        this.setRgbNumerics(oDiv, gDiv, pDiv, alreadyUsedInd, resDiv);
    };


    Selections.prototype.setRybNumerics = function (r, y, b) {
        $("#divPickR1").html(Math.round(r));
        $("#divPickY1").html(Math.round(y));
        $("#divPickB1").html(Math.round(b));
    };


    Selections.prototype.setRgbNumerics = function (o, g, p, alreadyUsedInd, rOrYorB) {

        $("#divPickR2").html(Math.round(o));
        $("#divPickG2").html(Math.round(g));
        $("#divPickB2").html(Math.round(p));
        rOrYorB = Math.round(rOrYorB);


        $("#divPickX").html(rOrYorB);


    };
    Selections.prototype.setOgpNumerics = function (o, g, p, alreadyUsedInd, rOrYorB) {

        $("#divPickO3").html(Math.round(o));
        $("#divPickG3").html(Math.round(g));
        $("#divPickP3").html(Math.round(p));
        rOrYorB = Math.round(rOrYorB);

        switch (alreadyUsedInd) {
            case 0:
                $("#divPickO3").html(Math.round(o) + ',' + rOrYorB);
                break;
            case 1:
                $("#divPickG3").html(Math.round(g) + ',' + rOrYorB);
                break;
            case 2:
                $("#divPickP3").html(Math.round(p) + ',' + rOrYorB);
                break;
        }


    };

    Selections.prototype.getCurDivColor = function () {

        Selections.prototype.curDivColor = colorDivs.getDivRYB(Selections.prototype.selDivId);

    };

    Selections.prototype.getCurDivColorCmyk = function () {

        Selections.prototype.curDivColor = colorDivs.getDivCmyk(Selections.prototype.selDivId);

    };

    Selections.prototype.drawPie = function (r, y, b) {

        colorPie = document.getElementById("colorPie").getContext("2d");

        pieData.datasets[0].data = [r, y, b];

        if (pieChart != null) {
            pieChart.destroy();
        }
        pieChart = new Chart(colorPie, {
            type: 'doughnut',
            data: pieData,
            options: pieOptions
        });

        //$("#colorPie").addClass(".colorPie");
    };
}


function simpleRect(rgb, i, j, centerX, centerY, offsetX, offsetY) {
    var newCell = "<div  id='gridcell-" + i + "_" + j + "' class='tip gradient' data-mode='top' data-tip=''></div>";
    $("#colorGrid").append(newCell);

    $("#gridcell-" + i + "_" + j).css({
        position: "absolute",
        "margin-left": centerY + offsetY, "margin-top": centerX + offsetX,
        /*top: centerY + offsetY, left: centerX + offsetX,*/
        top: 50,
        width: 40,
        height: 40
    });

    $('#gridcell-' + i + "_" + j).css('background-color', rgb);

}

function initPalletes() {

    $("#palletesToLighter").click(function () {
        if (darkness == -5) {
            return;
        }
        darkness--;
        //console.log(darkness);
        animateFramesToLeft();
    });
    $(" #palletesToDarker").click(function () {
        if (darkness == 5) {
            return;
        }
        darkness++;
        //console.log(darkness);
        animateFramesToRight();
    });

    copyHiddenRightFramesFromMiddle();
    copyHiddenLeftFramesFromMiddle();
}

function animateFramesToLeft() {
    updateLeftFrame();
    $("#palletes_frames").animate(
        {
            marginLeft: 0
        },
        500,
        function () {
            resetFramesOffset();
            copyMiddleFrameColorDivsFromLeft(24);
            repaintColorSector(24);
        }
    );
}

function animateFramesToRight() {
    updateRightFrame();
    $("#palletes_frames").animate(
        {
            marginLeft: -1500
        },
        500,
        function () {
            resetFramesOffset();
            copyMiddleFrameColorDivsFromRight(24);
            repaintColorSector(24);

        }
    );
}


function resetFramesOffset() {

    $("#palletes_frames").css({"margin-left": "-750px"});
}

function updateRightFrame() {
    repaintColorSectorFromBase(2, 24, darkness);
    repaintColorSectorFromBase(-1, 24, darkness + 2);
}

function updateLeftFrame() {
    repaintColorSectorFromBase(-1, 24, darkness);
    repaintColorSectorFromBase(2, 24, darkness - 2);


}

function copyHiddenRightFramesFromMiddle() {
    $("#palletes_rects_2").html($("#palletes_rects_1").html());
    $.each($("#palletes_rects_2").children(), function (key, val) {
        //remove child id
        $(this).attr("id", $(this).attr("id") + "_s2");
    });
}

function copyMiddleFrameColorDivsFromRight(ndivs) {
    for (j = 1; j <= 7; j++) {
        for (var i = 0; i < ndivs; i++) {

            var rgb = 'blue';
            var divId = j + '_' + i;

            divFullId = 'mixing-palette-m' + divId;
            divRightFullId = divFullId + "_s2";


            divColor = colorDivs.getDivCmyk(divRightFullId);
            colorC = divColor.R;
            colorM = divColor.Y;
            colorY = divColor.B;
            colorK = divColor.K;


            colorDivs.setColorByDivisions(divFullId, colorC, colorM, colorY, colorK);

        }
    }
}

function copyMiddleFrameColorDivsFromLeft(ndivs) {
    for (j = 1; j <= 7; j++) {
        for (var i = 0; i < ndivs; i++) {

            var rgb = 'blue';
            var divId = j + '_' + i;

            divFullId = 'mixing-palette-m' + divId;
            divRightFullId = divFullId + "_s-1";

            divColor = colorDivs.getDivCmyk(divRightFullId);
            colorC = divColor.R;
            colorM = divColor.Y;
            colorY = divColor.B;
            colorK = divColor.K;

            colorDivs.setColorByDivisions(divFullId, colorC, colorM, colorY, colorK);
        }
    }
}

function copyHiddenLeftFramesFromMiddle() {
    $("#palletes_rects_-1").html($("#palletes_rects_1").html());
    $.each($("#palletes_rects_-1").children(), function (key, val) {
        //remove child id
        $(this).attr("id", $(this).attr("id") + "_s-1");
    });
}


function fillHiddenFrameAnew() {

}


function polarRect(destinationDivId, r, teta, rgb, i, centerX, centerY) {
    $("#" + destinationDivId).append("<div  id='mixing-palette-m" + i + "' class='tip' data-mode='top' data-tip=''></div>");

    $('#mixing-palette-m' + i).css({
        position: "absolute",
        marginTop: centerY + r * Math.sin(teta * Math.PI / 180.0),
        marginLeft: centerX + r * Math.cos(teta * Math.PI / 180.0),
        width: 40,
        height: 40
    });

    $('#mixing-palette-m' + i).css('background-color', rgb);
    $("#mixing-palette-m" + i).rotate(teta);
}

function getComplementOfColor(rPart, yPart, bPart) {
    /*r   y   b
     100
     50  50
     100
     50      50
     50  50  100  */

    partComplementY = 0.5 * rPart;
    partComplementB = 0.5 * rPart;

    partComplementR = 0.5 * yPart;
    partComplementB += 0.5 * yPart;

    partComplementR += 0.5 * bPart;
    partComplementY += 0.5 * bPart;

    return {r: partComplementR, y: partComplementY, b: partComplementB};

}


function drawCircles() {

    createColorCircleNew(1, 340, 340, 320, 24);
    createColorCircleNew(2, 340, 340, 320 - 45, 24);
    createColorCircleNew(3, 340, 340, 320 - 90, 24);
    createColorCircleNew(4, 340, 340, 320 - 135, 24);
    createColorCircleNew(5, 340, 340, 320 - 180, 24);
    createColorCircleNew(6, 340, 340, 320 - 225, 24);
    createColorCircleNew(7, 340, 340, 320 - 270, 24);

    memorizeBaseColorDivs(24);
}

function memorizeBaseColorDivs(ndivs) {
    for (j = 1; j <= 7; j++) {
        for (var i = 0; i < ndivs; i++) {

            var rgb = 'blue';
            var divId = j + '_' + i;

            divFullId = 'mixing-palette-m' + divId;

            divColor = colorDivs.getDivRYB(divFullId);
            baseColorC = divColor.R;
            baseColorM = divColor.Y;
            baseColorY = divColor.B;

            colorDivs.setBaseColorByDivisions(divFullId, baseColorC, baseColorM, baseColorY);
        }
    }
}


function createColorCircleNew(j, centerX, centerY, radius, ndivs) {

    /*
     (0,0,1) Y
     (0,1,1) R         G   (1,0,1)
     (0,1,0) M        C  (1,0,0)
     B (1,1,0)

     */


    var humanPerceptionShift = [0, 0.15, 0.15, 0.1, 0.05, 0.05, -0.02];


    var calculateCircleCellColor = function (cellDivNr, ndivs) {

        ndivsdiv3 = ndivs / 3;
        ndivsdiv6 = ndivs / 6;
        var rPart = 0;
        var yPart = 0;
        var bPart = 0;


        if (cellDivNr < ndivsdiv6) {
            rPart = 1;
            yPart = 0;
            bPart = 1 * cellDivNr / ndivsdiv6;
        } else if (cellDivNr < ndivsdiv3) {
            rPart = 1 - 1 * (cellDivNr - ndivsdiv6) / ndivsdiv6;
            yPart = 0;
            bPart = 1;
        } else if (cellDivNr < ndivsdiv3 + ndivsdiv6) {
            rPart = 0;
            yPart = 1 * (cellDivNr - ndivsdiv3) / ndivsdiv6;
            bPart = 1
        } else if (cellDivNr < 2 * ndivsdiv3) {
            rPart = 0;
            yPart = 1;
            bPart = 1 - 1 * (cellDivNr - ndivsdiv3 - ndivsdiv6) / ndivsdiv6;
        } else if (cellDivNr < 2 * ndivsdiv3 + ndivsdiv6) {
            rPart = 1 * (cellDivNr - 2 * ndivsdiv3) / ndivsdiv6;
            yPart = 1;
            bPart = 0;
        } else if (cellDivNr < ndivs) {
            rPart = 1;
            yPart = 1 - 1 * (cellDivNr - 2 * ndivsdiv3 - ndivsdiv6) / ndivsdiv6;
            bPart = 0;
        }

        return {rPart: rPart, yPart: yPart, bPart: bPart};
    };


    var angleStep = 360.0 / ndivs;
    //var myColorMixer=new ColorMixer();
    var myColorMixer = new CmykColorMixer();

    for (var i = 0; i < ndivs; i++) {

        var rgb = 'blue';
        var divId = j + '_' + i;
        //if((i==0) || (i==1)){

        polarRect("palletes_rects_1", radius, i * angleStep, rgb, divId, centerX, centerY);
        //}

        var colorParts = calculateCircleCellColor(i, ndivs);

        mainColorBaseR = colorParts.rPart;
        mainColorBaseY = colorParts.yPart;
        mainColorBaseB = colorParts.bPart;

        var mainColorR = mainColorBaseR;
        var mainColorY = mainColorBaseY;
        var mainColorB = mainColorBaseB;

        var complementColorBase = {r: 1 - mainColorBaseR, y: 1 - mainColorBaseY, b: 1 - mainColorBaseB};//getComplementOfColor(mainColorBaseR,mainColorBaseY,mainColorBaseB);

        var complementColorR = complementColorBase.r * ((j - 1) / 7.0 + humanPerceptionShift[j - 1]);
        var complementColorY = complementColorBase.y * ((j - 1) / 7.0 + humanPerceptionShift[j - 1]);
        var complementColorB = complementColorBase.b * ((j - 1) / 7.0 + humanPerceptionShift[j - 1]);

        var mixedColorR = mainColorR + complementColorR;
        var mixedColorY = mainColorY + complementColorY;
        var mixedColorB = mainColorB + complementColorB;

        rybDivsStr = mainColorR + ',' + mainColorY + ',' + mainColorB;
        rybDivCompsStr = complementColorR + ',' + complementColorY + ',' + complementColorB;
        divFullId = 'mixing-palette-m' + divId;
        //$('#'+divFullId).attr('data-tip',rybDivsStr+"\n"+rybDivCompsStr);
        colorDivs.setColorByDivisions(divFullId, mixedColorR, mixedColorY, mixedColorB);

        r = Math.min(mixedColorR, 1);
        y = Math.min(mixedColorY, 1);
        b = Math.min(mixedColorB, 1);

        myColorMixer.paintCmyk(r, y, b, 0, divFullId);


    }


}

function repaintColorSectorFromBase(seriesId, ndivs, darkness) {
    var myColorMixer = new CmykColorMixer();
    for (j = 1; j <= 7; j++) {
        for (var i = 0; i < ndivs; i++) {

            var rgb = 'blue';
            var divId = j + '_' + i;

            divFullId = 'mixing-palette-m' + divId;

            divColor = colorDivs.getBaseDivRYB(divFullId);
            baseColorC = divColor.R;
            baseColorM = divColor.Y;
            baseColorY = divColor.B;


            var c = Math.min(baseColorC, 1);
            var m = Math.min(baseColorM, 1);
            var y = Math.min(baseColorY, 1);

            if (darkness < 0) {

                suppress = (6 + darkness) / 6
                c *= suppress;
                m *= suppress;
                y *= suppress;
            }
            var k = darkness < 0 ? 0 : darkness / 6.0;
            console.log(k);

            divFullId = divFullId + "_s" + seriesId;
            colorDivs.setColorByDivisions(divFullId, c, m, y, k);

            /*if((j==1) && (i==0))
             {
             console.log(colorDivs.divs["mixing-palette-m1_0_s2"]);
             }*/
            myColorMixer.paintCmyk(c, m, y, k, divFullId);
        }
    }
}

function repaintColorSector(ndivs) {
    var myColorMixer = new CmykColorMixer();
    for (j = 1; j <= 7; j++) {
        for (var i = 0; i < ndivs; i++) {

            var rgb = 'blue';
            var divId = j + '_' + i;

            divFullId = 'mixing-palette-m' + divId;

            divColor = colorDivs.getDivCmyk(divFullId);
            colorC = divColor.R;
            colorM = divColor.Y;
            colorY = divColor.B;
            colorK = divColor.K;

            myColorMixer.paintCmyk(colorC, colorM, colorY, colorK, divFullId);
        }
    }
}

function colorCircle(j, centerX, centerY, radius, ndivs) {

    var colorMixer = new ColorMixer();

    var angleStep = 360.0 / ndivs;
    var myColorMixer = new ColorMixer();

    var rPart = 8;
    var yPart = 0;
    var bPart = 0;

    ndivsdiv3 = ndivs / 3;
    ndivsdiv6 = ndivs / 6;
    var rPartComplement = 0;
    var yPartComplement = ndivsdiv6;
    var bPartComplement = ndivsdiv6;

    for (var i = 0; i < ndivs; i++) {


        var rgb = 'blue';
        var divId = j + '_' + i;
        polarRect("palletes_rects_1", radius, i * angleStep, rgb, divId, centerX, centerY);

        rDiv = (8 - j + 1) * rPart;
        yDiv = (8 - j + 1) * yPart;
        bDiv = (8 - j + 1) * bPart;

        rDivComp = (j - 1) * rPartComplement;
        yDivComp = (j - 1) * yPartComplement;
        bDivComp = (j - 1) * bPartComplement;

        /*rDivComp=64;
         yDivComp=0;
         bDivComp=0;*/

        rybDivsStr = rDiv + ',' + yDiv + ',' + bDiv;
        rybDivCompsStr = rDivComp + ',' + yDivComp + ',' + bDivComp;
        divFullId = 'mixing-palette-m' + divId;
        //$('#'+divFullId).attr('data-tip',rybDivsStr+"\n"+rybDivCompsStr);
        colorDivs.setColorByDivisions(divFullId, rDiv, yDiv, bDiv);
        // colorDivs.setColorComplementByDivisions(i,rDivComp,yDivComp,bDivComp);


        r = rDiv * 32 + rDivComp * 32;
        y = yDiv * 32 + yDivComp * 32;
        b = bDiv * 32 + bDivComp * 32;


        myColorMixer.paintRyb(r, y, b, 'mixing-palette-m' + divId);
        /*$('#mixing-palette-m'+divId).click(function(){
         $(this).effect("highlight",0);
         });*/
        if (i < ndivsdiv3) {
            rPart--;
            yPart++;

        } else if (i < 2 * ndivsdiv3) {
            yPart--;
            bPart++;
        } else {
            bPart--;
            rPart++;
        }


        //complementI=cdivs+cdivs/2+ i;
        if (i < ndivsdiv6) {

            yPartComplement--;
            bPartComplement++;

        } else if (i < ndivsdiv3 + ndivsdiv6) {
            bPartComplement--;
            rPartComplement++;
        } else if (i < 2 * ndivsdiv3 + ndivsdiv6) {
            rPartComplement--;
            yPartComplement++;
        } else {
            yPartComplement--;
            bPartComplement++;

        }

    }

}

function ColorGrid(ndivs, offsetX, offsetY) {

    //var colorMixer=new ColorMixer();
    var colorMixer = new CmykColorMixer();

    for (var i = 0; i < ndivs; i++) {
        for (var j = 0; j < ndivs; j++) {
            var rgb = 'black';
            var divId = j + '_' + i;
            simpleRect(rgb, i, j, i * 60, j * 60, offsetX, offsetY);

        }

    }

    ColorGrid.prototype.fillGradient = function (topLeftSourceCellId, topRightSourceCellId, bottomLeftSourceCellId) {

        divColor = colorDivs.getDivRYB(topLeftSourceCellId);
        topLeftSourceCellR = divColor.R;
        topLeftSourceCellY = divColor.Y;
        topLeftSourceCellB = divColor.B;

        divColor = colorDivs.getDivRYB(topRightSourceCellId);
        topRightSourceCellR = divColor.R;
        topRightSourceCellY = divColor.Y;
        topRightSourceCellB = divColor.B;

        divColor = colorDivs.getDivRYB(bottomLeftSourceCellId);
        bottomLeftSourceCellR = divColor.R;
        bottomLeftSourceCellY = divColor.Y;
        bottomLeftSourceCellB = divColor.B;

        for (var j = 0; j < 10; j++) {
            for (var i = 0; i < 10; i++) {

                colorR = topLeftSourceCellR * (9 - i) * (9 - j) + topRightSourceCellR * i * (9 - j) + bottomLeftSourceCellR * j * (9 - i);
                colorY = topLeftSourceCellY * (9 - i) * (9 - j) + topRightSourceCellY * i * (9 - j) + bottomLeftSourceCellY * j * (9 - i);
                colorB = topLeftSourceCellB * (9 - i) * (9 - j) + topRightSourceCellB * i * (9 - j) + bottomLeftSourceCellB * j * (9 - i);

                divFullId = 'gridcell-' + i + '_' + j;
                colorDivs.setColorByDivisions(divFullId, colorR, colorY, colorB);

                colorMixer.paintRyb(colorR, colorY, colorB, 'gridcell-' + i + '_' + j);

            }
        }

    };

    ColorGrid.prototype.fillCmykGradient = function (topLeftSourceCellId, topRightSourceCellId, bottomLeftSourceCellId) {

        divColor = colorDivs.getDivRYB(topLeftSourceCellId);
        topLeftSourceCellR = divColor.R;
        topLeftSourceCellY = divColor.Y;
        topLeftSourceCellB = divColor.B;

        divColor = colorDivs.getDivRYB(topRightSourceCellId);
        topRightSourceCellR = divColor.R;
        topRightSourceCellY = divColor.Y;
        topRightSourceCellB = divColor.B;

        divColor = colorDivs.getDivRYB(bottomLeftSourceCellId);
        bottomLeftSourceCellR = divColor.R;
        bottomLeftSourceCellY = divColor.Y;
        bottomLeftSourceCellB = divColor.B;


        for (var j = 0; j < 12; j++) {

            var colorRrow = (topLeftSourceCellR * (12 - j) / 11.0) + bottomLeftSourceCellR * (j) / 11.0;
            var colorYrow = (topLeftSourceCellY * (12 - j) / 11.0) + bottomLeftSourceCellY * (j) / 11.0;
            var colorBrow = (topLeftSourceCellB * (12 - j) / 11.0) + bottomLeftSourceCellB * (j) / 11.0;

            for (var i = 0; i < 12; i++) {


                colorR = (colorRrow * (12 - i) / 11.0) + topRightSourceCellR * i / 11.0;
                colorY = (colorYrow * (12 - i) / 11.0) + topRightSourceCellY * i / 11.0;
                colorB = (colorBrow * (12 - i) / 11.0) + topRightSourceCellB * i / 11.0;

                divFullId = 'gridcell-' + i + '_' + j;
                colorDivs.setColorByDivisions(divFullId, colorR, colorY, colorB);

                colorMixer.paintCmyk(colorR, colorY, colorB, 0, 'gridcell-' + i + '_' + j);

            }
        }

    };

}

