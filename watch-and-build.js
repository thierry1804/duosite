const chokidar = require('chokidar');
const { exec } = require('child_process');
const path = require('path');

// Chemins à surveiller
const jsPath = path.join('public', 'js', '*.js');
const cssPath = path.join('public', 'css', '*.css');

// Exclure les fichiers minifiés
const excludePattern = /\.min\.(js|css)$/;

// Fonction pour exécuter npm run build
function runBuild() {
    console.log('\n🔄 Changements détectés, exécution de npm run build...');
    
    exec('npm run build', (error, stdout, stderr) => {
        if (error) {
            console.error(`❌ Erreur: ${error.message}`);
            return;
        }
        
        if (stderr) {
            console.error(`⚠️ Avertissement: ${stderr}`);
        }
        
        console.log(`✅ Build terminé avec succès:\n${stdout}`);
        console.log('👀 En attente de modifications...');
    });
}

// Initialiser le watcher
const watcher = chokidar.watch([jsPath, cssPath], {
    ignored: excludePattern,
    persistent: true
});

// Événements du watcher
watcher
    .on('ready', () => {
        console.log('👀 Surveillance des fichiers JS et CSS initialisée.');
        console.log('👀 En attente de modifications...');
    })
    .on('change', (filePath) => {
        console.log(`📝 Fichier modifié: ${filePath}`);
        
        // Vérifier si le fichier n'est pas déjà minifié
        if (!excludePattern.test(filePath)) {
            // Attendre un court instant pour s'assurer que le fichier est complètement écrit
            setTimeout(runBuild, 300);
        }
    });

// Gestion de l'arrêt propre
process.on('SIGINT', () => {
    console.log('\n👋 Arrêt de la surveillance...');
    watcher.close().then(() => {
        console.log('👋 Surveillance terminée.');
        process.exit(0);
    });
}); 