import './bootstrap.js';
import './js/bootstrap.min';
import './js/click-scroll';
import './js/custom';
import './js/jquery.min';
import './js/jquery.sticky';

const jQuery = require('jquery')
window.jQuery = jQuery
window.$ = jQuery;

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
