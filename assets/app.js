import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import 'bootstrap/dist/css/bootstrap.min.css';

import './turbo/turbo-util.js';
/*$(document).on('turbo:before-cache', () => {
    $('html').removeClass('swal2-shown swal2-height-auto');
    $('body').removeClass('swal2-shown swal2-height-auto modal-open').css({
        'padding-right': '',
        'overflow': ''
    });
    $('.swal2-container').attr('data-turbo-temporary', '');
    $('.modal-backdrop.fade.show').remove();
    $('.modal.fade.show').removeClass('show').css('display', 'none');

});*/