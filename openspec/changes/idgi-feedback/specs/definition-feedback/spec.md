# Spec Delta

## Purpose

Lets readers tell editors that a definition did not help them, by pressing "I don't get it" and optionally saying why, and lets editors review and handle those replies.

## ADDED Requirements

### Requirement: Pressing "I don't get it" is counted as before

Pressing the "I don't get it" button on a published term's page SHALL add one to that term version's "I don't get it" count, as it does today, without changing the term's last-updated date. The count and the score worked out from it MUST NOT change in any other way.

#### Scenario: Click counted

- **WHEN** a visitor presses "I don't get it" on a term page
- **THEN** that term version's "I don't get it" count goes up by one and its last-updated date is unchanged

### Requirement: A reply is offered after the click

After a visitor presses "I don't get it", the box SHALL offer an optional form asking what did not make sense. Sending it MUST be optional: a visitor who writes nothing still has their click counted, and nothing else is stored.

#### Scenario: Form offered

- **WHEN** a visitor presses "I don't get it"
- **THEN** the box thanks them and offers a short form asking what didn't make sense

#### Scenario: Visitor writes nothing

- **WHEN** a visitor presses "I don't get it" and leaves without using the form
- **THEN** only the click is counted and no reply is stored

### Requirement: Replies are anonymous

A reply SHALL store only the visitor's words, the term version they were reading and the date it was sent. It MUST NOT store a name, email address, IP address or any other detail about the visitor, and the form MUST ask visitors not to include personal details.

#### Scenario: What a reply keeps

- **WHEN** a visitor sends the reply "What does allocation concealment mean here?" from the page for version 3 of a term
- **THEN** a reply is stored with that text, version 3 of the term and the date, and nothing that identifies the visitor

#### Scenario: Privacy note

- **WHEN** a visitor sees the reply form
- **THEN** the form asks them not to include their name or other personal details

### Requirement: Reply length is limited

A reply SHALL hold between 1 and 1,000 characters, not counting spaces at either end. A reply outside those limits MUST be refused with a message saying why, and nothing is stored.

#### Scenario: Empty reply refused

- **WHEN** a visitor sends the form with only spaces in it
- **THEN** the reply is refused with a message asking them to say what didn't make sense, and nothing is stored

#### Scenario: Over-long reply refused

- **WHEN** a visitor sends a reply of 1,001 characters
- **THEN** the reply is refused with a message giving the 1,000-character limit, and nothing is stored

### Requirement: A reply needs a click first

A reply SHALL only be accepted from a visitor who has pressed "I don't get it" for that same term version earlier in the same browser session. Each click MUST allow at most one reply.

#### Scenario: Reply without a click

- **WHEN** a request sends a reply for a term without "I don't get it" having been pressed for it in that session
- **THEN** the reply is refused and nothing is stored

#### Scenario: Second reply for one click

- **WHEN** a visitor who has already sent a reply after one click sends another without pressing "I don't get it" again
- **THEN** the second reply is refused and nothing is stored

### Requirement: Automated replies are kept out

Replies SHALL be limited to a few a minute from any one visitor. A reply whose hidden anti-spam field has been filled in MUST be discarded without being stored, while the visitor is shown the same thanks as for a real reply.

#### Scenario: Too many replies

- **WHEN** one visitor sends more replies in a minute than the limit allows
- **THEN** the extra replies are refused and not stored

#### Scenario: Hidden field filled in

- **WHEN** a reply arrives with the hidden anti-spam field filled in
- **THEN** the visitor sees the usual thanks and no reply is stored

### Requirement: Only published terms take replies

Replies SHALL only be accepted for term versions that are published on the glossary.

#### Scenario: Unpublished term

- **WHEN** a reply is sent for a term version that is a draft, archived or an earlier revision
- **THEN** the reply is refused and nothing is stored

### Requirement: The visitor is thanked

After a reply is accepted, the box SHALL thank the visitor and say that the editors will read it, and MUST NOT offer the form again for that click.

#### Scenario: Reply sent

- **WHEN** a visitor sends a reply that is accepted
- **THEN** the box thanks them, says the editors will read it, and no longer shows the form

### Requirement: Replies are never shown publicly

Replies SHALL be visible only on the admin screens. They MUST NOT appear on any public page, in the `/v1` API or in the widget.

#### Scenario: Public term page

- **WHEN** a visitor who is not signed in opens a term page that has replies
- **THEN** no reply text appears on the page or in the data sent with it

### Requirement: Editors see a term's replies on its edit screen

The term edit screen SHALL list the replies for every version of the term, beside its "I don't get it" score: new replies first, then those dealt with, newest first within each. Each MUST show its text, date and version, and say when it was about an earlier version.

#### Scenario: Replies listed

- **WHEN** an editor of a term opens its edit screen and the term has two new replies and one dealt with
- **THEN** the two new replies are listed first, newest first, followed by the one dealt with, each with its text, date and version

#### Scenario: Reply about an earlier version

- **WHEN** a reply was sent about version 2 and the term is now at version 3
- **THEN** the reply is listed on the edit screen and marked as being about version 2, an earlier version

#### Scenario: Dealt-with replies tucked away

- **WHEN** an editor opens a term's edit screen
- **THEN** replies already dealt with are collapsed until the editor chooses to show them

### Requirement: Only a term's editors see its replies

Replies SHALL be shown only to administrators and to the editors of that term. Anyone else MUST NOT be able to see them.

#### Scenario: Contributor

- **WHEN** a signed-in contributor who is not an editor of a term opens that term's edit screen
- **THEN** they are refused, as they are today, and no replies are shown

### Requirement: Replies are shown as plain text

Reply text SHALL be shown exactly as written, as plain text. Markup in a reply MUST NOT be interpreted.

#### Scenario: Reply containing markup

- **WHEN** a reply contains `<b>bold</b> <script>alert(1)</script>`
- **THEN** the edit screen shows those characters as typed, and no markup or script takes effect

### Requirement: Editors mark replies as dealt with

Anyone who can edit a term SHALL be able to mark any of its replies as dealt with, and to mark it as new again. The reply MUST record who marked it dealt with and when. Other users MUST NOT be able to change a reply.

#### Scenario: Marked dealt with

- **WHEN** an editor of the term marks a new reply as dealt with
- **THEN** the reply moves to those dealt with, showing the editor's name and the date

#### Scenario: Marked new again

- **WHEN** an editor marks a reply that was dealt with as new again
- **THEN** it returns to the new replies, without a dealt-with name or date

#### Scenario: Someone else tries

- **WHEN** a signed-in user who is not an editor of the term, nor an administrator, tries to mark one of its replies as dealt with
- **THEN** the request is refused and the reply is unchanged

### Requirement: Administrators delete replies

Administrators SHALL be able to delete a reply, for example spam, after confirming. Editors who are not administrators MUST NOT be able to delete replies.

#### Scenario: Administrator deletes spam

- **WHEN** an administrator deletes a reply and confirms
- **THEN** the reply is removed for good

#### Scenario: Editor cannot delete

- **WHEN** an editor who is not an administrator tries to delete a reply
- **THEN** the request is refused and the reply remains

### Requirement: Replies outlast new versions

Saving a term as a new version, updating a version or rolling back SHALL leave its existing replies in place, still linked to the version they were sent about.

#### Scenario: New version saved

- **WHEN** an editor saves a term with two replies as a new version
- **THEN** both replies are still listed on the edit screen, marked as being about the earlier version
