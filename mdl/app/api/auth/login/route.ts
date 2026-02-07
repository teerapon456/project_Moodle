import { NextResponse } from 'next/server';
import { portalDb, moodleDb } from '@/lib/db';
import bcrypt from 'bcryptjs';
import crypto from 'crypto';

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const { username, password } = body;

    if (!username || !password) {
      return NextResponse.json({ success: false, error: 'Username and password required' }, { status: 400 });
    }

    // 1. Authenticate against Portal Database (Source of Truth)
    // We use any[] to cast the result in a generic way to avoid complex typing for now
    const [users] = await portalDb.execute<any[]>('SELECT * FROM users WHERE username = ?', [username]);
    const user = users[0];

    if (!user || !user.password_hash) {
      return NextResponse.json({ success: false, error: 'Invalid username or password' }, { status: 401 });
    }

    const isValid = await bcrypt.compare(password, user.password_hash);
    if (!isValid) {
      return NextResponse.json({ success: false, error: 'Invalid username or password' }, { status: 401 });
    }

    // 2. Get User Profile from Portal (using split names if available)
    const [portalProfiles] = await portalDb.execute<any[]>('SELECT * FROM moodle_users WHERE username = ?', [username]);
    let portalProfile = portalProfiles[0];

    // Fallback if view not found or empty
    if (!portalProfile) {
      portalProfile = {
        username: user.username,
        firstname: user.fullname || user.username || 'User',
        lastname: 'Moodle',
        email: user.email || `${user.username}@example.com`
      };
    } else {
      // Ensure values are not null
      portalProfile.firstname = portalProfile.firstname || user.fullname || user.username || 'User';
      portalProfile.lastname = portalProfile.lastname || '-';
      portalProfile.email = portalProfile.email || user.email || `${user.username}@example.com`;
    }

    // 3. Sync/Get User in Moodle Database
    const [moodleUsers] = await moodleDb.execute<any[]>('SELECT id FROM mdl_user WHERE username = ?', [username]);
    let moodleUserId: number;

    if (moodleUsers.length > 0) {
      moodleUserId = moodleUsers[0].id;
    } else {
      // JIT Provisioning
      const now = Math.floor(Date.now() / 1000);
      const [insertResult] = await moodleDb.execute<any>(
        `INSERT INTO mdl_user (
           auth, confirmed, policyagreed, deleted, suspended, mnethostid, 
           username, password, idnumber, firstname, lastname, email, 
           emailstop, phone1, phone2, institution, department, address, city, country, 
           lang, calendartype, theme, timezone, firstaccess, lastaccess, lastlogin, 
           currentlogin, lastip, secret, picture, descriptionformat, mailformat, 
           maildigest, maildisplay, autosubscribe, trackforums, timecreated, timemodified, trustbitmask
         ) VALUES (
           'manual', 1, 0, 0, 0, 1, 
           ?, 'not cached', '', ?, ?, ?, 
           0, '', '', '', '', '', 'Bangkok', 'TH', 
           'en', 'gregorian', '', '99', ?, ?, ?, 
           ?, '', '', 0, 1, 1, 
           0, 2, 1, 0, ?, ?, 0
         )`,
        [
          username,
          portalProfile.firstname,
          portalProfile.lastname || '',
          portalProfile.email,
          now, now, now, now, // timestamps
          now, now // timecreated, timemodified
        ]
      );
      moodleUserId = insertResult.insertId;
    }

    // 4. Generate/Get Web Service Token
    const serviceShortname = 'moodle_mobile_app';
    const [services] = await moodleDb.execute<any[]>('SELECT id FROM mdl_external_services WHERE shortname = ?', [serviceShortname]);

    // Default to id 1 if not found, but it should exist
    let serviceId = 1;
    if (services.length > 0) {
      serviceId = services[0].id;
    }

    const [tokens] = await moodleDb.execute<any[]>('SELECT token FROM mdl_external_tokens WHERE userid = ? AND externalserviceid = ? AND tokentype = 1', [moodleUserId, serviceId]);

    let token: string;
    if (tokens.length > 0) {
      token = tokens[0].token;
    } else {
      // Generate new token
      token = crypto.createHash('md5').update(crypto.randomBytes(32)).digest('hex');
      const now = Math.floor(Date.now() / 1000);
      await moodleDb.execute(
        'INSERT INTO mdl_external_tokens (token, tokentype, userid, externalserviceid, sid, contextid, creatorid, timecreated, lastaccess, validuntil, iprestriction) VALUES (?, 1, ?, ?, NULL, 1, ?, ?, 0, 0, NULL)',
        [token, moodleUserId, serviceId, moodleUserId, now] // creatorid = self
      );
    }

    return NextResponse.json({
      success: true,
      data: {
        token: token,
        user: {
          username: username,
          id: moodleUserId,
          fullname: `${portalProfile.firstname} ${portalProfile.lastname}`,
          firstname: portalProfile.firstname,
          lastname: portalProfile.lastname,
          email: portalProfile.email,
          avatar: ''
        }
      }
    });

  } catch (error) {
    console.error('Login route error:', error);
    return NextResponse.json({ success: false, error: 'Internal Server Error' }, { status: 500 });
  }
}