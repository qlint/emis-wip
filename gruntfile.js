module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uncss: {
			dist: {
				files: {
					'src/css/template.css' : ['index.html']
				}
			}
		},
		watch: {
			sass: {
				files: 'src/sass/*.scss',
				tasks: ['sass','cssmin']
			}
		},
		sass: {
			dist: {
				files: {
					'src/css/template.css' : 'src/sass/template.scss'
				}
			}
		},		
		cssmin: {
			my_target: {
				files: [{
					expand: true,
					cwd: 'src/css/',
					src: ['template.css','!*.min.css'],
					dest: 'src/css/',
					ext: '.min.css'
				}]
			}
		},
	});
	grunt.loadNpmTasks('grunt-uncss');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.registerTask('default',['watch']);
}