Author: Maziar Navabi.

-Purpose:
  Create simulation for real world mixture of colors (paints), in a subtractive manner (not RGB) , that can be utilized for printing or Artists paintings purposes.

-Functions:

  1- Create a subtractive color circle which can be scrolled to lighter and darker circles ( click on two light and dark control buttons on top left of the circle)

  2- Analyse C M Y K components of each color by clicking on it in the current color circle(shown on status bar as bar graphs). Alo show this in a pie chart.

  3- Give equivalent of yellow, blue and red component if feasible (as normally painting is done in those three principal colors).

  4- Be able to put two or more colors besides each other to get the feel of choosing a set of colors for particular design (will be saved in later versions as user favorites).

  5- Create a gradient of three chosen colors by putting them in three corners of a grid and make gradient of their mixtures ( first Select "T" radio button to
   move to Tertiary selection mode and then click and choose three colors on the color circle. The system will create the gradient of them on the right pane).

  6- Be able to zoom into an already rendered gradient to be able to fine tune to an appropriate mixture. The gauges on the status bar then will show the exact
   combination in terms of principal color proportions.

-Technical features:

  1- Using advanced positioning features of css to overlay items on top of each other(my custom made bar graphs in css and js). Dynamic change of css to make a hover and selected effect by drawing a border on right criteria.

  2- Doing advanced Jquery animations and css transitions ( when lighter and darker buttons are clicked).

  3- Advanced use of jquery to build dynamic html (such as square and polar grid of div elements).

  4- Using prototype keyword in javascript to deal with functions as classes and do object oriented programming using new keyword.

  5- Doing minification on Js using gulp to make the files production ready (small and dependent to only one obfuscated javascript file).

  6- Sass compile ready (But css not yet moved to sass in this version).

  7- Some advanced maths used for vector calculations on color mixture components as well as polar coordinate rotations.

-How to run
  Run mcolor.html when served on a webserver to get correct look. Running on filesystem is possible but gives different view scaling.


Note:
   This will be later transfered to an angularJs framework for better maintainability.


Important:
 Copyright Maziar Navabi, all rights reserved.