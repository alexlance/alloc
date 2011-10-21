"""alloccli subcommand for viewing a list of projects."""
from alloc import alloc

class projects(alloc):
  """Print a list of projects."""

  # Setup the options that this cli can accept
  ops = []
  ops.append((''  ,'help           ','Show this help.'))
  ops.append((''  ,'csv            ','Return the results in CSV format.'))
  ops.append(('p:','project=ID|NAME','A project ID, or a fuzzy match for a project name.'))
  ops.append(('f:','fields=LIST    ','The commar separated list of fields you would like printed, eg: "all" eg: "projectID,projectName"')) 

  # Specify some header and footer text for the help text
  help_text = "Usage: %s [OPTIONS]\n"
  help_text+= __doc__
  help_text+= "\n\n%s\n\nIf called without arguments this program will display all of your projects."


  def run(self,command_list):

    # Get the command line arguments into a dictionary
    o, remainder = self.get_args(command_list, self.ops, self.help_text)

    # Got this far, then authenticate
    self.authenticate();

    # Initialize some variables
    #self.quiet = o['quiet']
    personID = self.get_my_personID()

    # Get a projectID either passed via command line, or figured out from a project name
    filter = {}
    if self.is_num(o['project']):
      filter["projectID"] = o['project']
    elif o['project']:
      filter["projectID"] = self.search_for_project(o['project'],personID)

    filter["personID"] = personID
    filter["projectStatus"] = "Current"

    fields = o["fields"] or "projectID,projectName"

    projects = {}
    projects = self.get_list("project",filter)

    self.print_table("project", projects, fields, sort="projectName")
      


