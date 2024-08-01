const importer_titles = document.getElementsByClassName("importer-title");
    if( importer_titles ){
        [...importer_titles].forEach((val, index) => {
            if( val?.outerText === 'WordPress'){
                document.getElementsByClassName("importer-title")[index].innerText = 'WordPress Content'
                val?.outerText === 'WordPress Content'
            }
            })
    } 

    const node = document.createElement("div");
node.innerHTML = "<div style='margin:auto;' id='migration-progress-modal'>hii ramya</div>";

node.style.position =  "absolute";
node.style.top =  "0";
node.style.bottom =  "0";
node.style.right =  "0";
node.style.left =  "0";
node.style.backgroundColor =  "white";
node.style.opacity =  "0.3";
node.style.display =  "none";

// document.getElementById("myList").appendChild(node);

document.getElementById("wpbody-content").appendChild(node)