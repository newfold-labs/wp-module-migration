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
node.innerHTML = "<div class='migrate-screen'> \
<div class='nfd-migration-loading'><span class='nfd-migration-loader'></span><h2 class='nfd-migration-title'> Let's migrate your existing site.</h2></div> \
Please wait a few seconds while we get your new account ready to import your existing WordPress site. \
</div>";

node.style.position =  "absolute";
node.style.top =  "0";
node.style.bottom =  "0";
node.style.right =  "0";
node.style.left =  "0";
node.style.backgroundColor =  "#ffffff5e";
node.style.display =  "none";
node.style.alignItems = "center";
node.style.justifyContent = "center";
    
node.setAttribute("id", "migration-progress-modal")
// document.getElementById("myList").appendChild(node);

document.getElementById("wpbody-content").appendChild(node)

    // Bind to the click event of the Run Importer link
    document.querySelector('a[href*="import=site_migration_wordpress_importer"]').addEventListener('click', function(e) {
        e.preventDefault(); 
        document.getElementById("migration-progress-modal").style.display = "flex";
        // debugger;
        window.location.href = this.getAttribute('href');
    });
