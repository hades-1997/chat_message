"use strict"; // Start of use strict

// Toggle the side navigation
$("#sidebarToggle, #sidebarToggleTop").on('click', function(e) {
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled");
    if ($(".sidebar").hasClass("toggled")) {
        $('.sidebar .collapse').collapse('hide');
    };
});

// Close any open menu accordions when window is resized below 768px
$(window).resize(function() {
    if ($(window).width() < 768) {
        $('.sidebar .collapse').collapse('hide');
    };
});

// Prevent the content wrapper from scrolling when the fixed side navigation hovered over
$('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function(e) {
    if ($(window).width() > 768) {
        var e0 = e.originalEvent,
            delta = e0.wheelDelta || -e0.detail;
        this.scrollTop += (delta < 0 ? 1 : -1) * 30;
        e.preventDefault();
    }
});


// Fn to allow an event to fire after all images are loaded
$.fn.imagesLoaded = function() {

    // get all the images (excluding those with no src attribute)
    var $imgs = this.find('img[src!=""]');
    // if there's no images, just return an already resolved promise
    if (!$imgs.length) {
        return $.Deferred().resolve().promise();
    }

    // for each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
    var dfds = [];
    $imgs.each(function() {

        var dfd = $.Deferred();
        dfds.push(dfd);
        var img = new Image();
        img.onload = function() {
            dfd.resolve();
        }
        img.onerror = function() {
            dfd.resolve();
        }
        img.src = this.src;

    });

    // return a master promise object which will resolve when all the deferred objects have resolved
    // IE - when all the images are loaded
    return $.when.apply($, dfds);

}

// Mobile window size
let vh = window.innerHeight * 0.01;
document.documentElement.style.setProperty('--vh', `${vh}px`);
window.addEventListener('resize', () => {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
});


function getCaret(el) {
    if (el.selectionStart) {
        return el.selectionStart;
    } else if (document.selection) {
        el.focus();
        var r = document.selection.createRange();
        if (r == null) {
            return 0;
        }
        var re = el.createTextRange(),
            rc = re.duplicate();
        re.moveToBookmark(r.getBookmark());
        rc.setEndPoint('EndToStart', re);
        return rc.text.length;
    }
    return 0;
}

function get_gifs(tenor_api_key, tenor_gif_limit, q) {
    loading(".gifs", "show");
    $('.gif-list').empty();
    if (q != "") {
        var api_url = `https://api.tenor.com/v1/search?key=` + tenor_api_key + `&media_filter=minimal&limit=` + tenor_gif_limit + `&q=` + q;
    } else {
        var api_url = `https://api.tenor.com/v1/trending?key=` + tenor_api_key + `&media_filter=minimal&limit=` + tenor_gif_limit;
    }
    $.get(api_url, function(data) {
        $.each(data.results, function(k, v) {
            var gif_url = v.media[0]['tinygif']['url'];
            var gif_li = `<li class="send-gif" data-gif="` + gif_url + `"><img class="gif-preview" src="` + gif_url + `"></li>`;
            $(gif_li).appendTo($('.gif-list'));
        });
    });
    loading(".gifs", "hide");
}

function linkParse(inputText) {
    var replacedText, replacePattern1, replacePattern2, replacePattern3;

    //URLs starting with http://, https://, or ftp://
    replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank"><span class="chat-link"><i class="fa fa-link"></i> $1</span></a>');

    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
    replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank"><span class="chat-link"><i class="fa fa-link"></i> $2</span></a>');

    //Change email addresses to mailto:: links.
    replacePattern3 = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
    replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1"><span class="chat-link"><i class="fa fa-link"></i> $1</span></a>');

    return replacedText;
}


function loading(div, status) {
    $(div).LoadingOverlay(status, {
        image: "",
        custom: '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>',
    });
}

function youtube_parser(url) {
    var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
    var match = url.match(regExp);
    return (match && match[7].length == 11) ? match[7] : false;
}

// save profile details
var page_reload = false;
$(".save-profile").on('click', function(e) {
    var data = new FormData($('#profile-form')[0]);
    var url = $('#save_profile_url').val();
    $('.profile-error').hide();
    $.ajax({
        url: url,
        data: data,
        type: "POST",
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        enctype: 'multipart/form-data',
        success: function(data) {

            $('.text-error').remove();
            if (data.success == true) {
                page_reload = true;
                $('.profile-success').html("Successfully updated");
                $('.profile-success').show();
            } else {
                if ($.isArray(data.message)) {
                    $.each(data.message, function(key, field_array) {
                        $.each(field_array, function(field, error_list) {
                            $.each(error_list, function(error_key, error_message) {
                                $('[name=' + field + ']').after(`<small class="form-text text-danger text-error">` + error_message + `</small>`);
                            });
                        });
                    });
                } else {
                    $('.profile-error').html(data.message);
                    $('.profile-error').show();
                }
            }
        },
    });
});

// profile image upload
$(document).on("change", ".upload-image", function() {
    var uploadFile = $(this);
    var files = !!this.files ? this.files : [];
    if (!files.length || !window.FileReader) return; // no file selected, or no FileReader support

    if (/^image/.test(files[0].type)) { // only image file
        var reader = new FileReader(); // instance of the FileReader
        reader.readAsDataURL(files[0]); // read the local file
        reader.onloadend = function() { // set image data as background of div
            uploadFile.closest(".imgUp").find('.imagePreview').html("");
            uploadFile.closest(".imgUp").find('.imagePreview').css("background-image", "url(" + this.result + ")");
        }
    }
});

$('#modalProfile').on('hidden.bs.modal', function() {
    if (page_reload) {
        page_reload = false;
        window.location.reload();
    }
});

// Sanitize xss jQuery - Clean xss and HTML
(function($) {
    $.sanitize = function(input) {
        //strip all html tags
        var output = input.replace(/<script[^>]*?>.*?<\/script>/gi, '').
        replace(/<[\/\!]*?[^<>]*?>/gi, '').
        replace(/<style[^>]*?>.*?<\/style>/gi, '').
        replace(/<![\s\S]*?--[ \t\n\r]*>/gi, '');
        return $.trim(output);
    };
})(jQuery);


function htmlEncode(html) {
    return document.createElement('a').appendChild(
        document.createTextNode(html)).parentNode.innerHTML;
};

// init date dropdown
$(".dob").dateDropdowns();
