<?php
#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  schools from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  openSIS is  web-based, open source, and comes packed with features that 
#  include student demographic info, scheduling, grade book, attendance, 
#  report cards, eligibility, transcripts, parent portal, 
#  student portal and more.   
#
#  Visit the openSIS web site at http://www.opensis.com to learn more.
#  If you have question regarding this system or the license, please send 
#  an email to info@os4ed.com.
#
#  This program is released under the terms of the GNU General Public License as  
#  published by the Free Software Foundation, version 2 of the License. 
#  See license.txt.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#***************************************************************************************
function PDFStop($handle)
{	global $OutputType,$htmldocAssetsPath,$filename,$publish_parents,$items;
	$dir = 'assets/studentfiles';

	if($publish_parents || $OutputType=='PDF')
	{
		$html = ob_get_contents();
		ob_end_clean();
		$html =  '<HTML><BODY>'.$html.'</BODY></HTML>';
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0, charset=utf-8");
		$temp_filename=date("Y-m-d-h-m-s");
		$html_file=$temp_filename . '.html';
		$pdf_file =$temp_filename . '.pdf';
		$myfile = fopen($html_file, "w") or die("Unable to open file!");
		fwrite($myfile, $html);
		fclose($myfile);
		$command="/usr/local/bin/wkhtmltopdf -B 10 -L 10 -R 10 -T 10 -s letter --enable-forms     --encoding utf8 " . $html_file . ' ' . $pdf_file;
		shell_exec($command);

		// Header content type
		//header('Content-type: application/pdf');
		//header('Content-Disposition: inline; filename="' . $filename . '.pdf' . '"');
		//header('Content-Transfer-Encoding: binary');
		//header('Accept-Ranges: bytes');
		// Read the file
		//@readfile($pdf_file);
		if($publish_parents){
			$destination_path = $dir;
            $target_path = $dir . '/' . $publish_parents . '-' . $filename . '.pdf';
			$fileType='application/pdf';
			$content = 'IN_DIR';
			$concat_filename = str_replace($dir.'/', '', $target_path);
			$concat_filename = str_replace("'", "\'", $concat_filename);
			if(file_exists($target_path)){
				DBQuery('DELETE FROM user_file_upload WHERE USER_ID=\''  . $publish_parents . '\'AND NAME=\'' . $concat_filename . '\'');
				unlink($target_path);
			}
			rename($pdf_file, $target_path);
			DBQuery('INSERT INTO user_file_upload (USER_ID,PROFILE_ID,SCHOOL_ID,SYEAR,NAME, SIZE, TYPE, CONTENT,FILE_INFO) VALUES (' . $publish_parents . ',\'3\',' . UserSchool() . ',' . UserSyear() . ',\'' . $concat_filename . '\', \'' . filesize($target_path) . '\', \'' . $fileType . '\', \'' . $content . '\',\'stufile\')');
			unlink($html_file);
			$items++;
			echo $items;
			echo ' -- ';
			echo 'Le bulletin pour l\'élève ';
			echo $publish_parents;
			echo ' à été publié aux parents ----> ';
			echo'<a href="/assets/studentfiles/'. $concat_filename .'" download="'. $filename .'" style="text-decoration: underline;">'. $filename .'</a>';
			echo '<br>';

		}
	}
	else
	{
	 	
		$html = ob_get_contents();
		ob_end_clean();
		$html =  '<HTML><BODY>'.$html.'</BODY></HTML>';
		echo $html;
	}
}
?>