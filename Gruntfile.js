module.exports = function(grunt){
  var pkg = grunt.file.readJSON('package.json');

  // for compile less files
  var lessConfig = {
      development: {
        options: {
          compress: false
        },
        files: [
          {
            src: [
              './haik-contents/css/less/haik.less'
            ],
            dest: './haik-contents/css/haik.css'
          },
          {
            src: [
              './haik-contents/css/less/admin.less'
            ],
            dest: './haik-contents/css/admin.css'
          }
        ]
      }
  };

  // for copy bower bootstrap files
  var copyBootstrapConfig = {
    files: []
  };

  var themeDirs = grunt.file.glob.sync('./haik-contents/skin/*/');

  // create less config
  for (var i in themeDirs) {
    var dir = themeDirs[i];
    
    var themeLess = grunt.file.glob.sync(dir + 'less/bootstrap.less');

    if (themeLess.length === 0) continue;

    lessConfig.development.files.push({
        src: themeLess,
        dest: dir + 'css/bootstrap-custom.css'
    });
  }

  // create copy:bootstrap config
  for (var i in themeDirs) {
    var dir = themeDirs[i];

    copyBootstrapConfig.files.push({
        expand: true,
        cwd: './bower_components/bootstrap/dist/',
        src: '**/*',
        dest: dir
    });
    copyBootstrapConfig.files.push({
        expand: true,
        cwd: './bower_components/bootstrap/',
        src: 'less/**/*',
        dest: dir
    });
  }


  grunt.initConfig({
    less: lessConfig,

    copy: {
      bootstrap: copyBootstrapConfig
    },

	watch: {
      less: {
        files: [
          './haik-contents/css/less/*.less',
          './haik-contents/skin/*/less/*.less'
        ],
        tasks: ['less'],
        options: {
          liveoverload: true
        }
      },
    }
  });

  //matchdepでpackage.jsonから"grunt-*"で始まる設定を読み込む
  require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

  grunt.registerTask('default', ['less', 'watch']);
  grunt.registerTask('publish', ['less']);
};
