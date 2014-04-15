module.exports = function(grunt){
  var pkg = grunt.file.readJSON('package.json');

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
  

  var themeDirs = grunt.file.glob.sync('./haik-contents/skin/*/');
  for (var i in themeDirs) {
    var dir = themeDirs[i];
    
    var themeLess = grunt.file.glob.sync(dir + 'less/bootstrap.less');

    if (themeLess.length === 0) continue;

    lessConfig.development.files.push({
        src: themeLess,
        dest: './haik-contents/skin/' + dir + '/css/bootstrap-custom.css'
    });
  }


//console.log(lessConfig.development.files);return;

  grunt.initConfig({
    less: lessConfig,
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
