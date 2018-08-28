var coll = document.getElementsByClassName("collapsible");
var content = document.getElementsByClassName("content");
var subcontent = document.getElementsByClassName("subcontent");
var i;
        
// onClick for all collapsible rows
for (i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function(x,collapsibleId) {
        return function(){
            toggleContent(x,collapsibleId);
        }
    }(i,coll[i].getAttribute('name')));
}

// onClick for all collapsible content rows
for (i = 0; i < content.length; i++) {
    content[i].addEventListener("click", function(x,contentId,child) {
        return function(){
            toggleSubContent(x,contentId,child);
        }
    }(i,content[i].getAttribute('name'),content[i].getAttribute('child')));
}

//collapse or expand all content on click
function toggleContent(i, collapsibleClassName){
    var content_number = document.getElementsByName("c" + collapsibleClassName);
    for (j = 0; j < content_number.length; j++) {
        if (content_number[j].style.visibility === 'visible') {
            content_number[j].style.visibility = 'collapse';

            coll[i].querySelectorAll(".c0 .dropdown")[0].style.transform = 'rotate(-90deg)';
        } else {
            content_number[j].style.visibility = 'visible';

            coll[i].querySelectorAll(".c0 .dropdown")[0].style.transform = 'rotate(0deg)';
        }
        var subcontent_number = document.getElementsByName("c" + collapsibleClassName + "s" + j);

        //collapse all subcontent when collapsing content
        for (k = 0; k < subcontent_number.length; k++) {
            if (subcontent_number[k].style.visibility === 'visible') {
                subcontent_number[k].style.visibility = 'collapse';
                
                content_number[j].querySelectorAll(".c1 .dropdown")[0].style.transform = 'rotate(-90deg)';
            } 
        }
    }
}

//collapse or expand all subcontent on click
function toggleSubContent(i,contentClassName,child){
    var subcontent_number = document.getElementsByName(contentClassName + child);
    for (j = 0; j < subcontent_number.length; j++) {
        if (subcontent_number[j].style.visibility === 'visible') {
            subcontent_number[j].style.visibility = 'collapse';

            content[i].querySelectorAll(".c1 .dropdown")[0].style.transform = 'rotate(-90deg)';
        } else {
            subcontent_number[j].style.visibility = 'visible';
            
            content[i].querySelectorAll(".c1 .dropdown")[0].style.transform = 'rotate(0deg)';
        }
    }
}


    



