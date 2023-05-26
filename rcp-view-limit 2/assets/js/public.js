function rcpvl() {
    // current page url
    var rcpvlPage = window.location.href;

    // remove cookies if user is logged in
    if (rcpvlLoggedIn == true) {
        Cookies.remove('rcpvl-visited');
    } else {
        if (rcpvlActive) {

            // if visited cookie array hasn't been created yet, create it
            if (Cookies.get('rcpvl-visited') == null) {
                // set expiration
                Cookies.set('rcpvl-visited', rcpvlPage, { expires: Number(rcpvlExpires) });
            } else {
                // split cookie value into an array
                var array = Cookies.get('rcpvl-visited').split(',');
                // redirect if length of array is more than limit, but not if already on redirect to page
                if (array.length >= rcpvlLimit && rcpvlPage != rcpvlRedirect && array.indexOf(rcpvlPage) === -1) {
                    window.location.href = rcpvlRedirect;
                    // if new page add it to array
                } else if (array.indexOf(rcpvlPage) === -1) {
                    array.push(rcpvlPage);
                    // convert array to string and store in cookie
                    Cookies.set('rcpvl-visited', array.toString(), { expires: Number(rcpvlExpires) });
                } else {
                    // if page limit hasn't been reached and page isn't new do nothing
                }
                document.querySelector("#rcpvl-count").innerHTML = rcpvlLimit - array.length;
            }

        }
        // countdown hasn't started
        if (Cookies.get('rcpvl-visited') == null) {
            document.querySelector("#rcpvl-count").innerHTML = rcpvlLimit;
            // begin countdown based on existing array
        } else {
            var array = Cookies.get('rcpvl-visited').split(',');
            document.querySelector("#rcpvl-count").innerHTML = rcpvlLimit - array.length;
        }
    }
}
rcpvl();

// hide count bar on close button click
if (document.querySelector("#rcpvl-hide-notice") !== null) {
    document.querySelector("#rcpvl-hide-notice").onclick = function () {
        document.querySelector("#rcpvl-notice").style.visibility = "hidden";
        document.querySelector("#rcpvl-notice").style.opacity = "0";
    }
}