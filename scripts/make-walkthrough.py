"""Create a captioned usage video from verified application screenshots."""
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tmp/video-deps'))
from PIL import Image, ImageDraw, ImageFont
import imageio_ffmpeg

out = ROOT / 'output/submission'
out.mkdir(parents=True, exist_ok=True)
slides = [
    ('02-businesses.jpg', '1. Add your business', 'Open Businesses > Add business. Enter name, email, phone and a logo.'),
    ('03-create-business.jpg', '2. Save the business', 'All fields are required. Use JPG, PNG or WebP images up to 4 MB.'),
    ('04-branches.jpg', '3. Add a branch', 'Open Branches > Add branch. Select its business and add branch images.'),
    ('05-weekly-slots.jpg', '4. Set weekly hours', 'Enable a weekday. Add separate slots for split shifts; leave other days closed.'),
    ('05-weekly-slots.jpg', '5. Check time slots', 'End must follow start. Duplicate and overlapping slots are rejected.'),
    ('06-special-date.jpg', '6. Add special dates', 'Available means open all day. Unavailable means closed all day in Asia/Kolkata.'),
    ('01-branch-details.jpg', '7. View hours and current status', 'Weekly hours and date exceptions determine status. Status refreshes every 30 seconds.'),
    ('07-date-opening.jpg', '8. A date overrides the weekly routine', 'This branch is open at night because today is explicitly marked available all day.'),
    ('04-branches.jpg', '9. Find or delete records', 'Search and sort either directory. Delete a business to remove its branches and files.'),
    ('11-edit-business.jpg', '10. Edit a business', 'Use Edit in Businesses. Update contacts and optionally replace the logo, then save.'),
    ('10-edit-branch.jpg', '11. Edit a branch', 'Update its business, name, weekly slots and dates. An empty upload retains existing images.'),
]
font_path = 'C:/Windows/Fonts/segoeui.ttf'
font = ImageFont.truetype(font_path, 36)
title_font = ImageFont.truetype(font_path, 48)
cmd = [imageio_ffmpeg.get_ffmpeg_exe(), '-y', '-loglevel', 'error', '-f', 'rawvideo',
       '-vcodec', 'rawvideo', '-pix_fmt', 'rgb24', '-s', '1920x1080', '-r', '10',
       '-i', '-', '-an', '-vcodec', 'libx264', '-preset', 'fast', '-crf', '21',
       '-pix_fmt', 'yuv420p', '-movflags', '+faststart', str(out / 'business-timing-walkthrough.mp4')]
proc = subprocess.Popen(cmd, stdin=subprocess.PIPE)
for filename, title, caption in slides:
    canvas = Image.new('RGB', (1920, 1080), '#182742')
    draw = ImageDraw.Draw(canvas)
    draw.text((55, 20), title, font=title_font, fill='white')
    source = Image.open(ROOT / 'docs/screenshots' / filename).convert('RGB')
    # Display the relevant section without shrinking a full long form to illegibility.
    if filename == '06-special-date.jpg':
        source = source.crop((0, 1320, source.width, min(source.height, 2050)))
    elif filename == '05-weekly-slots.jpg':
        source = source.crop((0, 450, source.width, min(source.height, 1400)))
    elif filename == '01-branch-details.jpg':
        source = source.crop((0, 250, source.width, 910))
    elif filename == '07-date-opening.jpg':
        source = source.crop((130, 350, 1300, 1010))
    else:
        source = source.crop((0, 0, source.width, min(source.height, 1050)))
    source.thumbnail((1810, 820))
    canvas.paste(source, ((1920 - source.width)//2, 100))
    words, lines, line = caption.split(), [], ''
    for word in words:
        test = (line + ' ' + word).strip()
        if draw.textlength(test, font=font) > 1810:
            lines.append(line)
            line = word
        else:
            line = test
    lines.append(line)
    for index, line in enumerate(lines):
        draw.text((55, 960 + index * 45), line, font=font, fill='white')
    frame = canvas.tobytes()
    for _ in range(80):
        proc.stdin.write(frame)
proc.stdin.close()
if proc.wait() != 0:
    raise RuntimeError('Video encoding failed')
print(out / 'business-timing-walkthrough.mp4')
