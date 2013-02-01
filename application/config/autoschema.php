<?php

AutoSchema::table('bills', function($table)
{
    $table->increments('id');
    $table->integer('user_id')->label('User')->values('users:id,username');
    $table->string('title');
    $table->string('name');
    $table->text('comments');
    $table->string('amount', 10);
    $table->string('recurrence'); // Weekly, Monthly or Yearly
    $table->integer('renews_on'); // Weekly = day of week, Monthly/Yearly = day of year
    $table->boolean('send_reminder'); // Should an email reminder be sent
    $table->integer('reminder'); // How many days before renewal to send reminder
    $table->boolean('include_in_totals');
    $table->timestamps();
});

AutoSchema::table('users', function($table)
{
    $table->increments('id');
    $table->string('forename', 255);
    $table->string('surname', 255);
    $table->string('username', 255);
    $table->string('email', 255);
    $table->string('password', 255);
    $table->string('hash', 255);
    $table->boolean('active');
    $table->timestamps();
});

/*
AutoSchema::table('bills', function($table)
{
    $table->increments('id');
    $table->integer('user_id');
    $table->string('title');
    $table->string('name');
    $table->text('comment');
    $table->string('amounts');
    $table->string('recurrence'); // Weekly, Monthly or Yearly
    $table->integer('renews_on'); // Weekly = day of week, Monthly/Yearly = day of year
    $table->boolean('send_reminder'); // Should an email reminder be sent
    $table->integer('reminder'); // How many days before renewal to send reminder
    $table->boolean('include_in_totals');
    $table->timestamps();
});

AutoSchema::table('users', function($table)
{
    $table->increments('id');
    $table->string('forename', 255);
    $table->string('surname', 255);
    $table->string('username', 255);
    $table->integer('email')->values('emails:id,email');
    $table->string('communication_pref')->label('Communcation Preferences')->values(array('email'=>'Email', 'phone'=>'Phone', 'mail'=>'Mail'))->attributes(array('formtype'=>'checkbox'));
    $table->string('password', 255);
    $table->string('hash', 255);
    $table->boolean('active');
    $table->timestamps();
});

AutoSchema::table('emails', function($table)
{
    $table->increments('id');
    $table->string('user_id', 255);
    $table->string('email', 255);
    $table->boolean('active');
    $table->timestamps();
});


AutoSchema::view('users_vw', function($view){
    $view->definition("SELECT * FROM users where active = 1 AND id > 100 AND id < 200");
});


AutoSchema::table("comms_wysiwyg", function($table){
    $table->increments("id");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
    $table->string("title")->rules("required");
    $table->text("text")->rules("required");
    $table->string("image")->label("Image File (max 200px wide)");
    $table->integer("image__size");
    $table->string("image__type");
    $table->timestamp("image__date");
});

AutoSchema::table("comms_image_articles", function($table){
    $table->increments("id");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
    $table->string("title")->rules("required");
    $table->string("image")->label("Image File (max 600px wide)")->rules("required");
    $table->integer("image__size");
    $table->string("image__type");
    $table->timestamp("image__date");
});

AutoSchema::table("training_recipients", function($table){
    $table->increments("id");
    $table->string("member_no")->label("Membership Number");
    $table->string("title")->label("Title")->rules("required");
    $table->string("firstname")->label("First Name")->rules("required");
    $table->string("lastname")->label("Last Name")->rules("required");
    $table->string("address")->label("Address")->rules("required");
    $table->string("source")->label("Source");
    $table->string("format")->label("Format");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("student_recipients", function($table){
    $table->increments("id");
    $table->string("member_no")->label("Membership Number");
    $table->string("title")->label("Title")->rules("required");
    $table->string("firstname")->label("First Name")->rules("required");
    $table->string("lastname")->label("Last Name")->rules("required");
    $table->string("address")->label("Address")->rules("required");
    $table->string("source")->label("Source");
    $table->string("format")->label("Format");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("health_recipients", function($table){
    $table->increments("id");
    $table->string("member_no")->label("Membership Number");
    $table->string("title")->label("Title")->rules("required");
    $table->string("firstname")->label("First Name")->rules("required");
    $table->string("lastname")->label("Last Name")->rules("required");
    $table->string("address")->label("Address")->rules("required");
    $table->string("source")->label("Source");
    $table->string("format")->label("Format");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("freelance_recipients", function($table){
    $table->increments("id");
    $table->string("member_no")->label("Membership Number");
    $table->string("title")->label("Title")->rules("required");
    $table->string("firstname")->label("First Name")->rules("required");
    $table->string("lastname")->label("Last Name")->rules("required");
    $table->string("address")->label("Address")->rules("required");
    $table->string("source")->label("Source");
    $table->string("format")->label("Format");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("blast_recipients", function($table){
    $table->increments("id");
    $table->string("member_no")->label("Membership Number");
    $table->string("title")->label("Title")->rules("required");
    $table->string("firstname")->label("First Name")->rules("required");
    $table->string("lastname")->label("Last Name")->rules("required");
    $table->string("address")->label("Address")->rules("required");
    $table->string("source")->label("Source");
    $table->string("format")->label("Format");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});


AutoSchema::table("sites", function($table){
    $table->increments("id");
    $table->string("name")->label("Name")->rules("required");
    $table->string("title")->label("Site Title")->rules("required");
    $table->string("eservices_base_url")->label("Base URL for eServices (with protocol)");
    $table->text("address")->label("Address");
    $table->string("town")->label("Town");
    $table->string("county")->label("County");
    $table->string("postcode")->label("Postcode");
    $table->string("telno")->label("Telephone number");
    $table->string("faxno")->label("Fax number");
    $table->string("email")->label("Email to display");
    $table->string("email_from")->label("Label emails as being from");
    $table->string("email_unsubscribe")->label("Email to unsubscribe from newsletters");
    $table->string("regno")->label("Company registration number");
    $table->string("vatno")->label("VAT registration number");
    $table->string("year")->label("Year of Copyright for Site");
    $table->string("integ")->label("Integra-esque folder ( /folder )");
    $table->string("banner_transition")->label("Banner transition effect");
    $table->string("banner_transition_speed")->label("Banner transition speed");
    $table->integer("pos");
    $table->boolean("layout")->label("Custom layout?");
    $table->string("gverify")->label("Google Verification Code");
    $table->text("robots")->label("Robots.txt");
    $table->string("liveurl")->label("Live URL (enter when site goes live)");
    $table->boolean("crewbus_update")->label("Enable Crewbus updates");
    $table->boolean("crewbus_search")->label("Enable Crewbus search");
    $table->boolean("crewbus_employers")->label("Enable Crewbus employers");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("siteurls", function($table){
    $table->increments("id");
    $table->string("siteurl")->label("URL")->rules("required");
    $table->integer("siteref")->label("Ref of Site")->rules("required");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("pages", function($table){
    $table->integer("id");
    $table->integer("siteref")->label("Ref of Site")->rules("required");
    $table->string("name")->label("Name (URL part)")->rules("required");
    $table->string("pagetitle")->label("Page Title")->rules("required");
    $table->string("menutitle")->label("Menu Title")->rules("required");
    $table->boolean("protected")->label("For logged in users only");
    $table->string("url")->label("Link to");
    $table->string("keywords")->label("Keywords");
    $table->string("description")->label("Description");
    $table->text("content")->label("Content");
    $table->boolean("is_content_after")->label("Place content after any custom content ?");
    $table->boolean("is_bodyblox_after")->label("Place BodyBlox after main page content?");
    $table->integer("form_ref")->label("Associated form");
    $table->integer("news_topic")->label("News Topic");
    $table->integer("poll_ref")->label("Poll to display");
    $table->integer("vert_container_ref")->label("Vertical container to display");
    $table->integer("horiz_container_ref")->label("Horizontal container to display");
    $table->boolean("in_menus")->label("Show in navigation?");
    $table->boolean("in_header")->label("Show in header?");
    $table->boolean("in_footer")->label("Show in footer?");
    $table->boolean("docs")->label("Can have documents?");
    $table->boolean("locked")->label("Page is locked?");
    $table->boolean("userkids")->label("Can have child pages?");
    $table->boolean("sectionadmin")->label("Is a module?");
    $table->string("pagesection")->label("Module folder name");
    $table->integer("parent")->label("Ref of Parent page")->rules("required");
    $table->integer("depth")->rules("required");
    $table->string("path")->label("Path");
    $table->boolean("sitemap")->label("Show on sitemap.xml");
    $table->string("changefreq")->label("Change Frequency");
    $table->integer("priority")->label("Priority");
    $table->integer("status");
    $table->string("sysauthor")->label("Author");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
    $table->timestamp("live_created");
});

AutoSchema::table("pages_draft", function($table){
    $table->increments("id");
    $table->integer("siteref")->label("Ref of Site")->rules("required");
    $table->string("name")->label("Name (URL part)")->rules("required");
    $table->string("pagetitle")->label("Page Title")->rules("required");
    $table->string("menutitle")->label("Menu Title")->rules("required");
    $table->boolean("protected")->label("For logged in users only");
    $table->string("url")->label("Link to");
    $table->string("keywords")->label("Keywords");
    $table->string("description")->label("Description");
    $table->text("content")->label("Content");
    $table->boolean("is_content_after")->label("Place content after any custom content ?");
    $table->boolean("is_bodyblox_after")->label("Place BodyBlox after main page content?");
    $table->integer("form_ref")->label("Associated form");
    $table->integer("news_topic")->label("News Topic");
    $table->integer("poll_ref")->label("Poll to display");
    $table->integer("vert_container_ref")->label("Vertical container to display");
    $table->integer("horiz_container_ref")->label("Horizontal container to display");
    $table->boolean("in_menus")->label("Show in navigation?");
    $table->boolean("in_header")->label("Show in header?");
    $table->boolean("in_footer")->label("Show in footer?");
    $table->boolean("docs")->label("Can have documents?");
    $table->boolean("locked")->label("Page is locked?");
    $table->boolean("userkids")->label("Can have child pages?");
    $table->boolean("sectionadmin")->label("Link to module from page tree");
    $table->string("pagesection")->label("Module folder name");
    $table->integer("parent")->label("Ref of Parent page")->rules("required");
    $table->integer("depth")->rules("required");
    $table->string("path")->label("Path");
    $table->boolean("sitemap")->label("Show on sitemap.xml");
    $table->string("changefreq")->label("Change Frequency");
    $table->integer("priority")->label("Priority");
    $table->integer("pos");
    $table->integer("status");
    $table->string("sysauthor")->label("Author");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("jargon", function($table){
    $table->increments("id");
    $table->varchar("sites")->label("Site(s)");
    $table->string("phrase")->label("Phrase or Acronym")->rules("required");
    $table->string("explanation")->label("Explanation")->rules("required");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("campaign_boxes", function($table){
    $table->increments("id");
    $table->string("title")->label("Title")->rules("required");
    $table->string("banner")->label("Image (max 304x117)");
    $table->integer("banner__size");
    $table->string("banner__type");
    $table->timestamp("banner__date");
    $table->text("content")->label("Content");
    $table->string("url")->label("Link to");
    $table->boolean("active");
    $table->integer("pos");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("training_boxes", function($table){
    $table->increments("id");
    $table->string("title")->label("Title")->rules("required");
    $table->string("banner")->label("Image (max 304x177)");
    $table->integer("banner__size");
    $table->string("banner__type");
    $table->timestamp("banner__date");
    $table->text("content")->label("Content");
    $table->boolean("active");
    $table->integer("pos");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("vertical_containers", function($table){
    $table->increments("id");
    $table->varchar("sites")->label("Site(s)");
    $table->string("title")->label("Title")->rules("required");
    $table->text("content")->label("Content");
    $table->integer("rss")->label("Select an item to display RSS instead");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("horizontal_containers", function($table){
    $table->increments("id");
    $table->varchar("sites")->label("Site(s)");
    $table->string("title")->label("Title")->rules("required");
    $table->text("content")->label("Content");
    $table->integer("rss")->label("Select an item to display RSS instead");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("banners", function($table){
    $table->increments("id");
    $table->string("alt")->label("Alt text")->rules("required");
    $table->string("url")->label("Link to");
    $table->string("filename")->label("Picture must be 1117x181 pixels")->rules("required");
    $table->integer("filename__size");
    $table->string("filename__type");
    $table->timestamp("filename__date");
    $table->text("content")->label("Text");
    $table->string("video_url")->label("Video link");
    $table->string("thumb")->label("Video thumbnail must be 170x124 pixels");
    $table->integer("thumb__size");
    $table->string("thumb__type");
    $table->timestamp("thumb__date");
    $table->boolean("active");
    $table->varchar("created_at");
    $table->varchar("updated_at");
});

AutoSchema::table("userpages", function($table){
    $table->increments("id");
    $table->integer("source");
    $table->integer("target");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("pagealiases", function($table){
    $table->increments("id");
    $table->string("oldurl")->label("Old URL")->rules("required");
    $table->string("newurl")->label("New URL")->rules("required");
    $table->integer("statuscode")->label("Type");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("image_pool", function($table){
    $table->increments("id");
    $table->string("title")->label("Image title")->rules("required");
    $table->string("alt")->label("Alt text");
    $table->string("filename")->label("Picture")->rules("required");
    $table->integer("filename__size");
    $table->string("filename__type");
    $table->timestamp("filename__date");
    $table->varchar("created_at");
    $table->varchar("updated_at");
});

AutoSchema::table("didyoumean", function($table){
    $table->increments("id");
    $table->string("requested")->label("Requested term")->rules("required");
    $table->string("substitution")->label("Substitute term")->rules("required");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("topics_to_image_pool", function($table){
    $table->increments("id");
    $table->integer("source")->label("Topic ref");
    $table->integer("target")->label("Image ref");
    $table->integer("pos");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("editor_images", function($table){
    $table->increments("id");
    $table->string("alt_text")->label("Alternative text")->rules("required");
    $table->string("filename")->label("Image")->rules("required");
    $table->integer("filename__size");
    $table->string("filename__type");
    $table->timestamp("filename__date");
    $table->string("member_no")->label("Editor's member no.");
    $table->varchar("created_at");
    $table->varchar("updated_at");
});

AutoSchema::table("send_to_a_friend", function($table){
    $table->increments("id");
    $table->string("your_name")->label("Your name")->rules("required");
    $table->string("your_email")->label("Your email")->rules("required");
    $table->string("friends_name")->label("Your friend's name")->rules("required");
    $table->string("friends_email")->label("Your friend's email")->rules("required");
});

AutoSchema::table("blog_authors", function($table){
    $table->increments("id");
    $table->string("member_no")->label("Member No")->rules("required");
    $table->string("nickname")->label("Nickname (URL)")->rules("required");
    $table->string("display_name")->label("Display name")->rules("required");
    $table->text("statement")->label("Statement")->rules("required");
    $table->boolean("can_mass_assign_branches")->label("Mass assign branches");
    $table->boolean("can_mass_assign_divisions")->label("Mass assign divisions");
    $table->boolean("can_mass_assign_subdivisions")->label("Mass assign subdivisions");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("blog_posts", function($table){
    $table->increments("id");
    $table->integer("blog_author_ref")->label("Author")->rules("required");
    $table->string("title")->label("Title")->rules("required");
    $table->varchar("releasedate")->label("Release date");
    $table->text("intro")->label("Introduction")->rules("required");
    $table->text("content")->label("Content")->rules("required");
    $table->boolean("allow_comments")->label("Allow comments?");
    $table->boolean("show_comments")->label("Show comments?");
    $table->boolean("in_all_branches")->label("Assign to all branches");
    $table->boolean("in_all_divisions")->label("Assign to all divisions");
    $table->boolean("in_all_subdivisions")->label("Assign to all subdivisions");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("blog_comments", function($table){
    $table->increments("id");
    $table->integer("blog_post_ref")->label("Blog post ref")->rules("required");
    $table->string("author_name")->label("Author name")->rules("required");
    $table->text("content")->label("Content")->rules("required");
    $table->boolean("active");
    $table->timestamp("created_at");
    $table->timestamp("updated_at");
});

AutoSchema::table("divisions_to_blog_posts", function($table){
    $table->increments("id");
    $table->integer("source")->label("Division ref");
    $table->integer("target")->label("Blog post ref");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("subdivisions_to_blog_posts", function($table){
    $table->increments("id");
    $table->integer("source")->label("Subdivision ref");
    $table->integer("target")->label("Blog post ref");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("branches2blog_posts", function($table){
    $table->increments("id");
    $table->integer("source")->label("Branch ref");
    $table->integer("target")->label("Blog post ref");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("campaigns_to_blog_posts", function($table){
    $table->increments("id");
    $table->integer("source")->label("Campaign ref");
    $table->integer("target")->label("Blog post ref");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("topics_to_blog_posts", function($table){
    $table->increments("id");
    $table->integer("source")->label("Topic ref");
    $table->integer("target")->label("Blog post ref");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});

AutoSchema::table("blog_posts2pages", function($table){
    $table->increments("id");
    $table->integer("source");
    $table->integer("target");
    $table->integer("pos");
    $table->timestamp("updated_at");
    $table->timestamp("created_at");
});
*/