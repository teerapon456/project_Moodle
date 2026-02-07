import { NextResponse } from 'next/server';
import { moodleDb, portalDb } from '@/lib/db';

export async function GET(request: Request) {
  try {
    const authHeader = request.headers.get('Authorization');

    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return NextResponse.json(
        { success: false, error: 'Unauthorized: Missing token' },
        { status: 401 }
      );
    }

    const token = authHeader.split(' ')[1];

    // Validate token against Moodle DB
    const [tokens] = await moodleDb.execute<any[]>(
      `SELECT t.*, u.username, u.firstname, u.lastname, u.email 
         FROM mdl_external_tokens t
         JOIN mdl_user u ON t.userid = u.id
         WHERE t.token = ? AND t.tokentype = 1`,
      [token]
    );

    if (tokens.length === 0) {
      return NextResponse.json(
        { success: false, error: 'Unauthorized: Invalid token' },
        { status: 401 }
      );
    }

    const tokenData = tokens[0];
    // Check expiration if needed (validuntil) - usually 0 means no expiry for mobile tokens
    if (tokenData.validuntil > 0 && tokenData.validuntil < Math.floor(Date.now() / 1000)) {
      return NextResponse.json(
        { success: false, error: 'Unauthorized: Token expired' },
        { status: 401 }
      );
    }

    // Query Portal database for actual user role
    let userRole = 'learner';
    let isSiteAdmin = false;
    try {
      const [portalUsers] = await portalDb.execute<any[]>(
        `SELECT u.role_id, r.name as role_name 
         FROM users u 
         LEFT JOIN roles r ON u.role_id = r.id 
         WHERE u.username = ?`,
        [tokenData.username]
      );
      if (portalUsers.length > 0) {
        const roleName = portalUsers[0].role_name?.toLowerCase() || '';
        // Map Portal roles to Moodle App roles
        if (roleName.includes('admin') || roleName.includes('superadmin')) {
          userRole = 'admin';
          isSiteAdmin = true;
        } else if (roleName.includes('manager') || roleName.includes('hr')) {
          userRole = 'manager';
        } else if (roleName.includes('instructor') || roleName.includes('teacher')) {
          userRole = 'instructor';
        } else {
          userRole = 'learner';
        }
      }
    } catch (e) {
      console.error('Failed to query Portal for role:', e);
      // Fallback: all users are learners
    }

    const user = {
      id: tokenData.userid,
      username: tokenData.username,
      firstname: tokenData.firstname,
      lastname: tokenData.lastname,
      fullname: `${tokenData.firstname} ${tokenData.lastname}`,
      email: tokenData.email,
      userpictureurl: '', // simplify for now
      siteid: 1,
      sitename: 'MyHR Learning',
      is_site_admin: isSiteAdmin,
      role: userRole,
    };

    return NextResponse.json(user);

  } catch (error) {
    console.error('Get User API Error:', error);
    return NextResponse.json(
      { success: false, error: 'Internal Server Error' },
      { status: 500 }
    );
  }
}

