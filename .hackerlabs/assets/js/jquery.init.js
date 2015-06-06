/*! Timeline Theme - Copyright(c) 2012 http://serifly.com */
var $ = jQuery;
var easing = 'easeInOutQuad';
var animation = 250;
var $timelineItem = false;
var timelineSidebar = false;
var $timelineControls = false;
var $timelineItems = false;
var timelineOffset = 0;
var timeoutNavigation = false;
var windowResize = false;
if (typeof navigationLabel == 'undefined') var navigationLabel = 'Navigate...';

var parseDate = function(dateString)
{
   var v = dateString.split(' ');
   return new Date(Date.parse(v[1] + ' ' + v[2] + ', ' + v[5] + ' ' + v[3] + ' UTC'));
};

var relativeDate = function(dateString)
{
   var date = new Date();
   date.setTime(parseDate(dateString));

   var distanceSeconds = ((new Date() - date) / 1000);
   var distanceMinutes = Math.floor(distanceSeconds / 60);

   if (distanceMinutes == 0) return 'less than a minute ago';
   if (distanceMinutes == 1) return 'a minute ago';
   if (distanceMinutes < 45) return distanceMinutes + ' minutes ago';
   if (distanceMinutes < 90) return 'about 1 hour ago';
   if (distanceMinutes < 1440) return 'about ' + Math.round(distanceMinutes / 60) + ' hours ago';
   if (distanceMinutes < 2880) return '1 day ago';
   if (distanceMinutes < 43200) return Math.floor(distanceMinutes / 1440) + ' days ago';
   if (distanceMinutes < 86400) return 'about 1 month ago';
   if (distanceMinutes < 525960) return Math.floor(distanceMinutes / 43200) + ' months ago';
   if (distanceMinutes < 1051199) return 'about 1 year ago';

   return 'over ' + Math.floor(distanceMinutes / 525960) + ' years ago';
};

var buildTweet = function(tweet, time)
{
   var link = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i;
   tweet = tweet.replace(link, "<a href=\"$1\">$1</a>");

   tweet = tweet.replace(/\B@([\w-]+)/gm, function(username)
         {
            return '<a href="http://twitter.com/' + $.trim(username).replace('@', '') + '">' + username + '</a>';
         });

   tweet = tweet.replace(/(^|\s+)#(\w+)/gi, function(hash)
         {
            return '<a href="http://twitter.com/search?q=%23' + $.trim(hash).replace('#', '') + '">' + hash + '</a>';
         });

   return [tweet, relativeDate(time)];
};

var overlay = function(show)
{
   if (show)
   {
      if ($('#overlay').length === 0)
      {
         $('#wrapper').before('<div id="overlay"></div>');
      }

      $('#overlay').css({ opacity: 0, display: 'block' }).animate({ opacity: 0.9 }, animation, easing);
   }
   else
   {
      $('#overlay').animate({ opacity: 0 }, animation, easing, function()
            {
               $(this).remove();
            });
   }

   return false;
};

var loading = function(show)
{
   if (show)
   {
      if ($('#loading').length === 0)
      {
         $('#wrapper').before('<div id="loading"></div>');
      }

      $('#loading').css({ opacity: 0, display: 'block' }).animate({ opacity: 1 }, animation, easing);
   }
   else
   {
      $('#loading').remove();
   }

   return false;
};

var inView = function(el)
{
   var r, html;
   if ( !el || 1 !== el.nodeType ) { return false; }
   html = document.documentElement;
   r = el.getBoundingClientRect();

   return ( !!r
         && r.bottom >= 0
         && r.right >= 0
         && r.top <= $(window).height()
         && r.left <= $(window).width()
         );
};

var timeline = function(rebuild)
{
   var timelineLength = $timelineItems.length;

   if (timelineLength == 0)
   {
      return false;
   }

   if (typeof rebuild != 'undefined')
   {
      if (rebuild === 'prepare' || rebuild === 'resize')
      {
         if (transitionSupport)
         {
            $('#timeline .activeSlider ul').css(transitionSupportPrefix + 'transform', 'translateX(0px)');
         }
         else
         {
            $('#timeline .activeSlider ul').animate({ marginLeft: 0 }, animation, easing);
         }
      }

      if (rebuild === true || rebuild === 'resize')
      {
         $('#timeline .activeSlider').removeClass('activeSlider').find('li').attr('class', function(index, css)
               {
                  return css.replace(/\bslide-\S+/g, '');
               });

         $('#timeline ul.sliderControls').html('');
      }
   }

   if ($timelineControls !== false)
   {
      if ($(window).scrollTop() >= timelineOffset)
      {
         $timelineControls.css({ position: '', top: '120px' });
      }
      else
      {
         $timelineControls.css({ position: 'absolute', top: '' });
      }
   }

   $timelineItems.each(function()
         {
            if (inView($(this)[0]))
            {
               var eTop = $(this).offset().top;
               var $timelineItemTemp = $(this).parent();
               var timelineItemClass = $timelineItemTemp.attr('class').replace('element', '').replace('activeSlider', '').replace(' ', '');

               if (!$(this).is('.activeSlider'))
               {
                  $(this).find('ul.slider input').each(function()
                        {
                           $(this).parent().html('<img src="' + $(this).val() + '" />');
                        });

                  $(this).find('img').css({ opacity: 0 });
                  $(this).slider({ timeline: true });
               }

               if ((($(document).height() - ($(window).scrollTop() + $(window).height())) < ($(this).height() + timelineOffset)) || ((eTop - $(window).scrollTop() - (121 + timelineOffset)) < 0))
               {
                  $timelineItem = $timelineItemTemp;

                  $li = $('#timeline ul.navigation li');
                  $('a.active', $li).removeClass('active');
                  $('a[href=#' + timelineItemClass + ']', $li).addClass('active');
               }
            }
         });

   return false;
};

var iframeAutoWidth = function()
{
   $('iframe.autoWidth').each(function()
         {
            $(this).css({ height: parseInt(($(this).width() / 16) * 9) + 'px' });
         });
};

var galleryHeight = function()
{
   $('div.gallery').each(function()
         {
            $(this).css({ height: $(this).find('ul li img:visible').height() + 'px' });
         });
};

// DOM is ready
$(function()
      {
         $timelineItems = $('#timeline div.element div.slider');

         timeline();
         iframeAutoWidth();

         if (typeof url != 'undefined')
         {
            var preload = new Array
               (
                url + '/img/layout/loading.gif',
                url + '/img/layout/loadingDark.gif',
                url + '/img/layout/sliderControls.png',
                url + '/img/layout/zoom.png',
                url + '/img/layout/play.png',
                url + '/img/layout/close.png'
               );

            for (var i = 0; i < preload.length; i++)
            {
               $('<img />')[0].src = preload[i];
            }
         }

         $('<div class="mobileNavigation"><select /></div>').appendTo('#header .wrapper');
         $('<option />',
               {
                  'selected': 'selected',
                  'value': '',
                  'text': navigationLabel
               }).appendTo('#header select');
         $('#header .navigation a').each(function()
               {

                  var itemText = $(this).text()
                     var itemParent = $(this).parent().parent();

                  if ((itemParent.is('ul.children') && itemParent.parent().parent().is('ul.children')) || (itemParent.is('ul.sub-menu') && itemParent.parent().parent().is('ul.sub-menu')))
                  {
                     itemText = '-- ' + itemText;
                  }
                  else if ((itemParent.is('ul.children')) || (itemParent.is('ul.sub-menu')))
                  {
                     itemText = '- ' + itemText;
                  }

                  $('<option />',
                        {
                           'value': $(this).attr('href'),
                           'html': itemText
                        }).appendTo('#header select');
               });

         $('#header select').css({ opacity: 0 });

         $('#header select').change(function()
               {
                  window.location = $(this).find('option:selected').val();
               });

         $('#header .navigation li').hover(function()
               {
                  var $ul = $(this).find('ul:first');

                  if ($ul.length != 0)
                  {
                     if ($(this).parent().is('ul.navigation') || $(this).parent().parent().is('div.navigation'))
                     {
                        $ul.css({ opacity: 0, marginTop: '-10px' }).animate({ opacity: 1, marginTop: '0' }, 150, easing);
                     }
                     else
                     {
                        var offset = $ul.position();
                        var height = $ul.height();

                        if ($ul.parents('ul').height() < (height + (offset.top - 60)))
                        {
                           if ($('li.border', $ul).length == 0) $ul.prepend('<li class="border" style="height: ' + (height - 20) + 'px;"></li>');
                        }
                        else
                        {
                           $ul.find('li.border').remove();
                        }

                        $ul.css({ opacity: 0, marginLeft: '150px' }).animate({ opacity: 1, marginLeft: '160px' }, 150, easing);
                     }
                  }
               });

         if ($('#header a.logo img').length != 0)
         {
            $('#header a.logo img').one('load', function()
                  {
                     var height = $(this).height();

                     if (height != 60)
                     {
                        $(this).css({ marginTop: parseInt((80 - height) / 2) + 'px' }).parent().addClass('autoMarginTop');
                     }
                  }).each(function()
                     {
                        if (this.complete) $(this).load();
                     });
         }

         $('#content a img').parent().addClass('zoom');
         $('#content a img.alignleft').parent().addClass('alignleft');
         $('#content a img.alignright').parent().addClass('alignright');
         $('#content a img.aligncenter, #content a img.alignnone').parent().addClass('aligncenter');
         $('div.widgetColumn div.widget:first').css({ marginTop: 0 });

         if ($('#timeline').length != 0)
         {
            timelineOffset = $('#timeline').offset().top - 80;

            if ($('div.heroUnit').length != 0)
            {
               $timelineControls = $('#timeline ul.navigation');

               $timelineControls.css({ position: 'absolute' });
            }
         }

         $('#content').on('mouseenter', 'a.zoom', function()
               {
                  if (($(this).find('span.zoom').length != 0) || $(window).width() <= 480)
                  {
                     return false;
                  }

                  var $link = $(this);
                  var $image = $(this).find('img');
                  var link = $(this).attr('href');
                  var minus = 37;

                  if (link.search('.jpg') !== -1 || link.search('.jpeg') !== -1 || link.search('.png') !== -1 || link.search('.gif') !== -1)
                  {
                     var classValue = 'zoom';
                  }
                  else if (link.search('vimeo.com') !== -1 || link.search('youtube.com') !== -1 || link.search('youtu.be') !== -1)
                  {
                     var classValue = 'zoom play';
                  }
                  else
                  {
                     return false;
                  }

                  if ($(this).is('.alignright'))
                  {
                     var minus = 14;
                  }

                  if ($(this).is('.alignleft'))
                  {
                     var minus = 40;
                  }

                  $image.one('load', function()
                        {
                           var $isGallery = $link.parents('div.gallery');
                           var $isSlider = $link.parents('div.slider');

                           if ($isGallery.length != 0)
                           {
                              var width = $isGallery.width();
                              var height = $isGallery.height();
                           }
                           else
                           {
                              var width = $image.width();
                              var height = $image.height();
                           }

                           $(this).before('<span class="' + classValue + '" style="margin-left: ' + ((width / 2) - minus) + 'px; margin-top: ' + ((height / 2) - 28) + 'px;"></span>');

                           if ($isSlider.length != 0)
                           {
                              var imagePosition = $image.position();

                              if (Math.floor((imagePosition.left + width)) > $isSlider.width() || Math.floor(imagePosition.left) < 0)
                              {
                                 return false;
                              }
                           }

                           $(this).parent().find('span.zoom').animate({ opacity: 1 }, animation, easing);
                        }).each(function()
                           {
                              if (this.complete)
                              {
                                 $(this).load();
                              }
                           });
               });

         $('#content').on('mouseleave', 'a.zoom', function()
               {
                  $(this).find('span.zoom').animate({ opacity: 0 }, animation, easing, function()
                        {
                           $(this).remove();
                        });
               });

         $('#content').on('click', 'a.zoom', function(event)
               {
                  var link = $(this).attr('href');
                  var vimeoPattern = /^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/;
                  var vimeoMatch = vimeoPattern.exec(link);
                  var youtubePattern = /.*(?:youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=)([^#\&\?]*).*/;
                  var youtubeMatch = youtubePattern.exec(link);
                  var prevZoom = false;
                  var nextZoom = false;
                  var $gallery = $(this).parents('ul.slider');
                  var $galleryAlternative = $(this).parents('div.gallery').find('ul');

                  if ($(window).width() <= 480)
                  {
                     window.location.href = link;

                     return false;
                  }

                  if ($gallery.length != 0)
                  {
                     $gallery.addClass('activeGallery');

                     var $prevZoom = $(this).parents('li').prev('li');

                     if ($prevZoom.length != 0)
                     {
                        var prevZoom = $prevZoom.attr('class');
                     }

                     var $nextZoom = $(this).parents('li').next('li');

                     if ($nextZoom.length != 0)
                     {
                        var nextZoom = $nextZoom.attr('class');
                     }
                  }
                  else if ($galleryAlternative.length != 0)
                  {
                     $galleryAlternative.addClass('activeGallery').find('li').removeClass('nextZoom').removeClass('prevZoom');

                     var $prevZoom = $(this).parents('li').prev('li');

                     if ($prevZoom.length != 0)
                     {
                        var prevZoom = $prevZoom.addClass('prevZoom').attr('class');
                     }

                     var $nextZoom = $(this).parents('li').next('li');

                     if ($nextZoom.length != 0)
                     {
                        var nextZoom = $nextZoom.addClass('nextZoom').attr('class');
                     }
                  }

                  if (typeof event.which !== 'undefined')
                  {
                     overlay(true);
                  }

                  loading(true);

                  if (link.search('.jpg') !== -1 || link.search('.jpeg') !== -1 || link.search('.png') !== -1 || link.search('.gif') !== -1)
                  {
                     $('#wrapper').before('<img id="zoom" src="' + link + '" />');

                     $('#zoom').load(function()
                           {
                              var $image = $(this);
                              var imageRatio = 0;
                              var imageWidth = $(this).width();
                              var imageHeight = $(this).height();
                              var windowWidth = $(window).width();
                              var windowHeight = $(window).height();
                              var maxWidth = windowWidth - 100;
                              var maxHeight = windowHeight - 180;

                              if (imageWidth > maxWidth)
                              {
                                 imageRatio = maxWidth / imageWidth;
                                 imageWidth = imageWidth * imageRatio;
                                 imageHeight = imageHeight * imageRatio;
                              }

                              if (imageHeight > maxHeight)
                              {
                                 imageRatio = maxHeight / imageHeight;
                                 imageWidth = imageWidth * imageRatio;
                                 imageHeight = imageHeight * imageRatio;
                              }

                              $('#loading').animate({ opacity: 0 }, animation, easing, function()
                                    {
                                       $(this).remove();
                                    });

                              $image.css({ width: imageWidth + 'px', height: imageHeight + 'px', marginTop: '-' + ((imageHeight / 2) - 30) + 'px', marginLeft: '-' + ((imageWidth / 2) + 10) + 'px', opacity: 0, display: 'block' }).animate({ opacity: 1 }, animation, easing, function()
                                    {
                                       $image.after('<div id="zoomClose" style="margin-top: -' + ((imageHeight / 2) - 40) + 'px; margin-right: -' + ((imageWidth / 2) + 10) + 'px;"></div><div id="zoomPrev"' + ((prevZoom == false) ? ' class="inactive"' : '') + ' style="margin-top: -' + ((imageHeight / 2) - 80) + 'px; margin-right: -' + ((imageWidth / 2) + 10) + 'px;"><input type="hidden" value="' + ((prevZoom == false) ? '' : prevZoom) + '" /></div><div id="zoomNext"' + ((nextZoom == false) ? ' class="inactive"' : '') + ' style="margin-top: -' + ((imageHeight / 2) - 120) + 'px; margin-right: -' + ((imageWidth / 2) + 10) + 'px;"><input type="hidden" value="' + ((nextZoom == false) ? '' : nextZoom) + '" /></div>');
                                       $('#zoomClose, #zoomPrev, #zoomNext').stop(true, true).css({ display: 'block' }).animate({ marginRight: '-=30px' }, animation, easing);
                                    });
                           });

                     return false;
                  }
                  else if (vimeoMatch || youtubeMatch)
                  {
                     if (vimeoMatch)
                     {
                        var videoSrc = 'http://player.vimeo.com/video/' + vimeoMatch[5] + '?title=0&amp;byline=0&amp;portrait=0&amp;color=ffffff&amp;autoplay=1';
                     }
                     else if (youtubeMatch)
                     {
                        var videoSrc = 'http://www.youtube.com/embed/' + youtubeMatch[1] + '?rel=0&amp;autoplay=1';
                     }

                     if ($(window).width() < 800)
                     {
                        $('#wrapper').before('<div id="zoom" style="width: 640px; height: 360px; margin: -150px 0 0 -330px;"><iframe src="' + videoSrc + '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div><div id="zoomClose" style="margin-top: -140px; margin-right: -330px;"></div><div id="zoomPrev"' + ((prevZoom == false) ? ' class="inactive"' : '') + ' style="margin-top: -100px; margin-right: -330px;"><input type="hidden" value="' + ((prevZoom == false) ? '' : prevZoom) + '" /></div><div id="zoomNext"' + ((nextZoom == false) ? ' class="inactive"' : '') + ' style="margin-top: -60px; margin-right: -330px;"><input type="hidden" value="' + ((nextZoom == false) ? '' : nextZoom) + '" /></div>');
                     }
                     else
                     {
                        $('#wrapper').before('<div id="zoom" style="width: 800px; height: 450px; margin: -195px 0 0 -410px;"><iframe src="' + videoSrc + '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div><div id="zoomClose" style="margin-top: -185px; margin-right: -410px;"></div><div id="zoomPrev"' + ((prevZoom == false) ? ' class="inactive"' : '') + ' style="margin-top: -145px; margin-right: -410px;"><input type="hidden" value="' + ((prevZoom == false) ? '' : prevZoom) + '" /></div><div id="zoomNext"' + ((nextZoom == false) ? ' class="inactive"' : '') + ' style="margin-top: -105px; margin-right: -410px;"><input type="hidden" value="' + ((nextZoom == false) ? '' : nextZoom) + '" /></div>');
                     }

                     $('#zoom').css({ opacity: 0, display: 'block' });

                     var show = function()
                     {
                        $('#loading').animate({ opacity: 0 }, animation, easing, function()
                              {
                                 $(this).remove();
                              });

                        $('#zoom').animate({ opacity: 1 }, animation, easing, function()
                              {
                                 $('#zoomClose, #zoomPrev, #zoomNext').stop(true, true).css({ display: 'block' }).animate({ marginRight: '-=30px' }, animation, easing);
                              });
                     };

                     window.setTimeout(show, 1200);

                     return false;
                  }
                  else
                  {
                     window.location.href = link;
                  }
               });

         $(document).on('click', '#zoomPrev:not(.inactive), #zoomNext:not(.inactive)', function()
               {
                  $('#zoom, #zoomClose, #zoomPrev, #zoomNext').remove();
                  $('ul.activeGallery .' + $(this).find('input').val() + ' a.zoom').click();
               });

         $(document).on('click', '#overlay, #zoomClose', function()
               {
                  overlay(false);
                  loading(false);
                  $('ul.activeGallery').removeClass('activeGallery');
                  $('#zoom, #zoomClose, #zoomPrev, #zoomNext').animate({ opacity: 0 }, animation, easing, function()
                        {
                           $(this).remove();
                        });
               });

         $timelineItem = $('#timeline div.element:first');
         $('#header ul.timelineControl li').click(function()
               {
                  if ($(this).is('.prev'))
                  {
                     var $prevItem = $timelineItem.prev('div.element');

                     if ($prevItem.length != 0)
                     {
                        var offset = $prevItem.position();
                        $timelineItem = $prevItem;

                        if (offset != null) $('html, body').animate({ scrollTop: (offset.top + timelineOffset - 40) + 'px' }, animation, easing);
                     }
                     else
                     {
                        var $item = $('#timeline div.element:last');
                        var offset = $item.position();
                        $timelineItem = $item;

                        if (offset != null) $('html, body').animate({ scrollTop: (offset.top + timelineOffset - 40) + 'px' }, animation, easing);
                     }
                  }
                  else if ($(this).is('.next'))
                  {
                     var $nextItem = $timelineItem.next('div.element');

                     if ($nextItem.length != 0)
                     {
                        var offset = $nextItem.position();
                        $timelineItem = $nextItem;

                        if (offset != null) $('html, body').animate({ scrollTop: (offset.top + timelineOffset - 40) + 'px' }, animation, easing);
                     }
                     else
                     {
                        var $item = $('#timeline div.element:first');
                        var offset = $item.position();
                        $timelineItem = $item;

                        if (offset != null) $('html, body').animate({ scrollTop: (offset.top + timelineOffset - 40) + 'px' }, animation, easing);
                     }
                  }
                  else if ($(this).is('.sidebar'))
                  {
                     if (timelineSidebar)
                     {
                        return false;
                     }
                     else
                     {
                        timelineSidebar = true;
                     }

                     var $timelineSidebar = $('#timeline .timelineSidebar');

                     if (transitionSupport)
                     {
                        $('div.element', '#timeline').css(transitionSupportPrefix + 'transition', 'width 250ms ease-in-out');
                        $('div.slider, div.slider ul li a img', '#timeline').css(transitionSupportPrefix + 'transition', 'height 250ms ease-in-out');
                        $timelineSidebar.css(transitionSupportPrefix + 'transition', 'margin 250ms ease-in-out, opacity 250ms ease-in-out');
                     }

                     if (!$(this).is('.hidden'))
                     {
                        $(this).addClass('hidden');
                        timeline('prepare');

                        $('#timeline').addClass('fullWidth');
                        $timelineSidebar.addClass('hidden');
                     }
                     else
                     {
                        $(this).removeClass('hidden');
                        timeline('prepare');

                        $('#timeline').removeClass('fullWidth');
                        $timelineSidebar.removeClass('hidden');
                     }

                     setTimeout(function()
                           {
                              timeline(true);

                              if (transitionSupport)
                              {
                                 $('div.element', '#timeline').css(transitionSupportPrefix + 'transition', '');
                                 $('div.slider, div.slider ul li a img', '#timeline').css(transitionSupportPrefix + 'transition', '');
                                 $timelineSidebar.css(transitionSupportPrefix + 'transition', '');
                              }

                              timelineSidebar = false;
                           }, 250, this);
                  }
               });

         $('#timeline ul.navigation li a').click(function()
               {
                  if ($(this).attr('href') != '')
                  {
                     $element = $('#timeline div.' + $(this).attr('href').replace('#', '') + ':first');

                     if ($element.length != 0)
                     {
                        var offset = $('#timeline div.' + $(this).attr('href').replace('#', '') + ':first').position();

                        $('html, body').animate({ scrollTop: (offset.top + timelineOffset - 40) + 'px' }, animation, easing);
                     }
                  }
                  else
                  {
                     return false;
                  }
               });

         $('#footer a.top').click(function()
               {
                  $('html, body').animate({ scrollTop: 0 }, animation, easing);

                  return false;
               });

         if (window.location.href.search('#') !== -1 && $('#timeline').length != 0)
         {
            var timelineClass = window.location.href.split('#')[1];
            var $link = $('#timeline ul.navigation li a[href="#' + timelineClass + '"]');

            if ($link)
            {
               $link.click();
            }
         }

         // Generate tab navigation
         if ($('div.tabWrapper').length != 0)
         {
            $('div.tabWrapper').each(function()
                  {
                     var printTabs = '<ul class="tabs">';
                     var tabContent =  $(this).find('div.tabContent');
                     var tabCount = tabContent.length;

                     $(tabContent).each(function(key)
                           {
                              if (key != 0)
                              {
                                 $(this).hide();
                              }

                              var label = $(this).find('input.tabLabel').val();

                              if (!label)
                              {
                                 label = 'Tab ' + (key + 1);
                              }

                              $(this).addClass('tab-' + key);
                              printTabs+= '<li class="tabTrigger-' + key + '">' + label + '</li>';
                           });

                     $(this).prepend(printTabs + '</ul>');
                     $(this).find('li:first').addClass('active');
                  });
         }

         // Handle click on tabs
         $('div.tabWrapper').delegate('ul.tabs li', 'click', function()
               {
                  if ($(this).is('.active'))
                  {
                     return false;
                  }

                  var id = $(this).attr('class').split('-');
                  id = id[1];

                  var parent = $(this).parent().parent();
                  parent.find('ul.tabs li').removeClass('active');
                  $(this).addClass('active');
                  parent.find('div.tabContent').hide()
                     parent.find('div.tab-' + id).animate({ opacity: 'show' }, animation, easing);
               });

         // Galleries
         var galleryActive = false;

         $('div.gallery').prepend('<div class="controls"><div class="prev"></div><div class="next"></div></div>');

         $('div.gallery').on('click', 'div.prev, div.next', function()
               {
                  if (galleryActive)
                  {
                     return false;
                  }
                  else
                  {
                     galleryActive = true;
                  }

                  var $gallery = $(this).parents('div.gallery');
                  var $currentImage = $gallery.find('li:visible');

                  if (typeof $gallery.attr('style') == 'undefined')
                  {
                     $gallery.css({ height: + $currentImage.height() + 'px' });
                  }

                  if ($(this).is('.prev'))
                  {
                     var $nextImage = $currentImage.prev('li');

                     if ($nextImage.length == 0)
                     {
                        $nextImage = $gallery.find('ul li:last');
                     }
                  }
                  else
                  {
                     var $nextImage = $currentImage.next('li');

                     if ($nextImage.length == 0)
                     {
                        $nextImage = $gallery.find('ul li:first');
                     }
                  }

                  $nextImage.show();
                  $currentImage.css({ position: 'absolute', top: 0, left: 0, zIndex: 1 }).animate({ opacity: 0 }, 500, easing, function()
                        {
                           $(this).css({ position: '', top: '', left: '', zIndex: '', opacity: '', display: 'none' });
                           galleryActive = false;
                        });
               });

         // Contact
         if ($('#contactForm').length != 0)
         {
            var labelName = $('#contactForm label[for="contactName"]').text();
            var labelEmail = $('#contactForm label[for="contactEmail"]').text();
            var labelMessage = $('#contactForm label[for="contactMessage"]').text();
            var emailPattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

            if ($('#contactVerify').length != 0)
            {
               var labelVerify = $('#contactForm label[for="contactVerify"]').text();
            }

            $('#contactForm button').click(function()
                  {
                     if ($('#Field7').val() == '')
                     {
                        $('#contactForm label[for="contactName"]').addClass('error').text(labelName + ' is required');
                     }
                     else
                     {
                        $('#contactForm label[for="contactName"]').removeClass('error').text(labelName);
                     }

                     if ($('#Field2').val() == '')
                     {
                        $('#contactForm label[for="contactEmail"]').addClass('error').text(labelEmail + ' is required');
                     }
                     else if (!emailPattern.test($('#Field2').val()))
                     {
                        $('#contactForm label[for="contactEmail"]').addClass('error').text(labelEmail + ' is invalid');
                     }
                     else
                     {
                        $('#contactForm label[for="contactEmail"]').removeClass('error').text(labelEmail);
                     }

                     if ($('#Field1').val() == '')
                     {
                        $('#contactForm label[for="contactMessage"]').addClass('error').text(labelMessage + ' is required');
                     }
                     else
                     {
                        $('#contactForm label[for="contactMessage"]').removeClass('error').text(labelMessage);
                     }

                     if (typeof labelVerify != 'undefined')
                     {
                        if ($('#contactVerify').val() == '')
                        {
                           $('#contactForm label[for="contactVerify"]').addClass('error').text(labelVerify + ' is required');
                        }
                        else
                        {
                           $('#contactForm label[for="contactVerify"]').removeClass('error').text(labelVerify);
                        }
                     }

                     if ($('#contactForm label.error').length == 0)
                     {
                        $('#contactForm form').append('<input type="hidden" value="1" name="contactValid" />');
                     }
                     else
                     {
                        return false;
                     }
                  });
         }

         // Handle widgets in large footer
         var largeFooter = $('#footer .widget');
         var largeFooterLength = largeFooter.length;
         if (largeFooterLength != 0)
         {
            var footerClass = '';
            var footerEachCount = 1;
            var footerFullCount = largeFooterLength;

            largeFooter.each(function(key)
                  {
                     if (largeFooterLength >= 4)
                     {
                        footerClass = 'oneFourth';
                     }
                     else if (largeFooterLength == 3)
                     {
                        footerClass = 'oneThird';
                     }
                     else if (largeFooterLength > 1)
                     {
                        footerClass = 'oneHalf';
                     }
                     else
                     {
                        footerClass = '';
                     }

                     $(this).removeClass('oneThird').addClass(footerClass);

                     if (key == (footerFullCount - 1) || footerEachCount == 4)
                     {
                        $(this).addClass('lastColumn').after('<div class="clearfix"></div><p class="separator"></p>');

                        if (footerEachCount == 4)
                        {
                           footerEachCount = 0;
                           largeFooterLength = largeFooterLength - 4;
                        }
                     }

                     footerEachCount++;
                  });
         }

         $(window).scroll(function()
               {
                  timeline();
               });

         var $divWrapper = $('div.wrapper');
         var windowResize = $divWrapper.width();
         $(window).resize(function()
               {
                  if (windowResize != $divWrapper.width())
                  {
                     windowResize = $divWrapper.width();
                     timeline('resize');
                     iframeAutoWidth();
                     galleryHeight();
                  }
               });
      });
