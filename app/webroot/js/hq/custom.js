$(document).ready(function(){$(".c-hamburger").click(function(){$(".menu").slideToggle("fast")}),function(){"use strict";function i(i){i.addEventListener("click",function(i){i.preventDefault(),this.classList.contains("is-active")===!0?this.classList.remove("is-active"):this.classList.add("is-active")})}for(var t=document.querySelectorAll(".c-hamburger"),e=t.length-1;e>=0;e--){var c=t[e];i(c)}}(),$("#log-in-pop").click(function(){$(".login-pop").slideToggle("shop-popup")})});
$(document).mouseup(function(e) 
{
    var container = $("#log-in-pop, .login-pop");

    // if the target of the click isn't the container nor a descendant of the container
    if (!container.is(e.target) && container.has(e.target).length === 0) 
    {
        container.slideUp();
    }
});