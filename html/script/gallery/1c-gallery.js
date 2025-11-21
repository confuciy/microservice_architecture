window.addEventListener("DOMContentLoaded", () => {
  // (A) GET ALL IMAGES
  var all = document.querySelectorAll(".gallery img");

  // (B) CLICK ON IMAGE TO TOGGLE FULLSCREEN
  if (all.length>0) { for (let img of all) {

    //img.onclick = () => img.classList.toggle("full");



    //img.onclick = () => alert(src);
    //img.onclick = () => img.setAttribute('src', src);
    img.onclick = () => (img.setAttribute('src', getImagePath(img)), img.classList.toggle("full"));
  }}
});

function getImagePath(img) {

  let src_arr = img.getAttribute('src').split('/');

  let src_length = src_arr.length - 1;

  if (src_arr[src_length].match(/preview_/)) {
    src_arr[src_length] = src_arr[src_length].replace(/preview_/,'');
  } else {
    src_arr[src_length] = 'preview_' + src_arr[src_length];
  }

  let src = src_arr.join('/');

  return src;
}