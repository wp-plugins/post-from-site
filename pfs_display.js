/* Currently it's almost pointless to have this as an external file,
 * but eventually I'll learn more about AJAX fanciness and will need
 * the extra space. 'Til then, oh well. If it bothers you, then you
 * probably know how to fix it. */
function getElementsByClassName(classname, node)  {
    if(!node) node = document.getElementsByTagName("body")[0];
    var a = [];
    var re = new RegExp('\\b' + classname + '\\b');
    var els = node.getElementsByTagName("*");
    for(var i=0,j=els.length; i<j; i++)
        if(re.test(els[i].className))a.push(els[i]);
    return a;
}
function pfsopen(id){
	postboxes = getElementsByClassName('pfs_postbox');
	for (i in postboxes){
		postboxes[i].style.display = 'none';
	}
	document.getElementById('pfs_postbox'+id).style.display = 'block';
	return;
}

function pfsclose(id){
	document.getElementById('pfs_postbox'+id).style.display = 'none';
	return;
}
