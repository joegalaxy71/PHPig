A simple hacked solution trying to protect php sites from hacking

DISCLAIMER: use at your own risk, the whole solution is experimental an no guarantee can be given. Make all the needed backups and keep checking if phpig alters files or databases in any way. Due to the nature of phpig, file corruption can happen unnoticed. Please take the necessary time to learn the configuration options.

OVERALL: Phpig runs before the php interpreter processes the requested page. Once invoked in such manner, it checks if a specific admin cookie is included in the http request. If present the cookie is validated and if the validation succeed, phpig exits, allowing the request to continue without altering anything on the php / zend side. This is called an "admin" request. If no admin cookie is present or the cookie fails to validate, phpig uses a config file relative to the domain of the request to substitute some critical php functions like fopen, move_uploaded_file, copy, rename, unlink ... with a wrapper function that essentially checks if the functions is going to create, modify, rename or delete .php files, thus effectively hacking the website. If such hacking effort is detected, a die() is called and the php execution for this single request is stopped. Phpig can be told to protect also other critical files like .tpl files, configuration files, log files and whole paths.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.

