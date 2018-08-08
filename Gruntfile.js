module.exports = function (grunt) {

    semver = require('semver');

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),
        phplint: {
            good: ["**/*.php"]
        },
        jsonlint: {
            sample: {
                src: ['composer.json', 'package.json'],
                options: {
                    formatter: 'prose'
                }
            }
        },
        version: {
            emconf: {
                options: {
                    prefix: '    \'version\' => \'',
                    replace: '[0-9a-zA-Z\\-_\\+\\.]+'
                },
                src: ['ext_emconf.php']
            },
            json: {
                src: ['composer.json', 'package.json']
            },
        },
        gitcommit: {
            versionfiles: {
                options: {
                    message: '[TASK] push version to <%= pkg.name %> v<%= pkg.version %>'
                },
                files: {
                    src: ['composer.json', 'package.json', 'ext_emconf.php']
                }
            },
            everything: {
                options: {
                    message: 'adding changed files not yet committed',
                    allowEmpty: true
                },
                files: {
                    src: ['.']
                }
            }
        },
        gitpush: {
            your_target: {
                options: {}
            }
        },
        shell: {
            flowpublish: {
                command: '~/scripts/git-flow-publish-this-version.sh',
            },
            options: {
                execOptions: {
                    maxBuffer: Infinity
                }
            },
            deployToServer: {
                command: [
                    '../typo3-wiwi_uni-due_de/deployment/extensionDeploy.sh',
                    '../typo3-msm_uni-due_de/deployment/extensionDeploy.sh 2>> /dev/null || true'
                ].join(';')
            },
        },
        versionadddevsuffix: {
            default: {}
        },
        readpkg: {
            default: {}
        },
        writeversion: {
            default: {}
        },
        watch: {
            scripts: {
                files: ['**/*'],
                tasks: ['build'],
                options: {
                    spawn: false
                }
            }
        }
    });


    grunt.loadNpmTasks("grunt-git");
    grunt.loadNpmTasks('grunt-jsonlint');
    grunt.loadNpmTasks("grunt-phplint");
    grunt.loadNpmTasks("grunt-shell");
    grunt.loadNpmTasks("grunt-version");
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerMultiTask('readpkg', 'Read in the package.json file', function () {

        grunt.config.set('pkg', grunt.file.readJSON('./package.json'));

    });
    grunt.registerMultiTask('versionadddevsuffix', 'Get Major.Minor.Patch and add `-alpha`', function () {
        var preReleaseSuffix = '-alpha';
        grunt.config.set('pkg', grunt.file.readJSON('./package.json'));
        var currentversion = grunt.config.get('pkg').version;
        var devsuffix = (semver.prerelease(currentversion) === null ? preReleaseSuffix : preReleaseSuffix + ((parseInt(('' + semver.prerelease(currentversion)).replace(/[^\d.]/g, ''), 10) > 0 ? parseInt(('' + semver.prerelease(currentversion)).replace(/[^\d.]/g, ''), 10) : 0) + 1))
        grunt.task.run('version::' + semver.major(currentversion) + '.' + semver.minor(currentversion) + '.' + (semver.patch(currentversion) + (semver.prerelease(currentversion) === null ? 1 : 0)) + devsuffix);

    });


    grunt.registerTask('default', ['test']);
    grunt.registerTask('develop', ['build', 'watch']);
    grunt.registerTask('test', ['phplint', 'jsonlint']);
    grunt.registerTask('build', ['test', 'shell:deployToServer']);
    grunt.registerTask('flowpublish', ['shell:flowpublish']);
    grunt.registerTask('release', ['version::patch', 'readpkg', 'gitcommit:versionfiles', 'gitcommit:everything', 'build', 'flowpublish', 'versionadddevsuffix', 'readpkg', 'gitcommit:versionfiles', 'gitpush']);
    grunt.registerTask('upload', ['gitcommit:everything', 'gitpush']);
};