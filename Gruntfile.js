module.exports = function(grunt) {


  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),
    phplint : {
      good : [ "**/*.php" ]
    },
    jsonlint : {
      sample : {
        src : [ 'composer.json', 'package.json' ],
        options : {
          formatter : 'prose'
        }
      }
    },
    version : {
      emconf : {
        options : {
          prefix : '    \'version\' => \'',
          replace : '[0-9a-zA-Z\\-_\\+\\.]+'
        },
        src : [ 'ext_emconf.php' ]
      },
      json : {
        src : [ 'composer.json', 'package.json' ]
      },
    },
    gitcommit : {
      versionfiles : {
        options : {
          message : '[TASK] push version to <%= pkg.version %>'
        },
        files : {
          src : [ '*.*', 'Classes/*' ]
        }
      },
      everything : {
        options : {
          message : 'adding files for <%= pkg.version %>'
        },
        files : {
          src : [ '*.*', 'Classes/*' ]
        }
      }
    },
    gitpush : {
      your_target : {
        options : {}
      }
    },
    shell : {
      flowpublish : {
        command : '~/scripts/git-flow-publish-this-version.sh',
      },
      options : {
        execOptions : {
          maxBuffer : Infinity
        }
      }
    },
    readpkg : {
      default : {}
    },
    writeversion : {
      default : {}
    }
  });

  grunt.loadNpmTasks("grunt-git");
  grunt.loadNpmTasks('grunt-jsonlint');
  grunt.loadNpmTasks("grunt-phplint");
  grunt.loadNpmTasks("grunt-shell");
  grunt.loadNpmTasks("grunt-version");
  
  grunt.registerMultiTask('readpkg', 'Read in the package.json file', function() {

    grunt.config.set('pkg', grunt.file.readJSON('./package.json'));

});


  grunt.registerTask('default', [ 'test' ]);
  grunt.registerTask('test', [ 'phplint', 'jsonlint' ]);
  grunt.registerTask('build', [ 'test' ]);
  grunt.registerTask('flowpublish', [ 'shell:flowpublish' ]);
  grunt.registerTask('release', [ 'version::patch','readpkg','gitcommit:versionfiles', 'build', 'flowpublish', 'version::prerelease', 'readpkg','gitcommit:versionfiles' ]);
  grunt.registerTask('upload', [ 'gitcommit:everything', 'gitpush' ]);
};